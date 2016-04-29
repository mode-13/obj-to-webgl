<?php
//***************************
//obj_to_webgl.php
//
//Jon Hellebuyck
//mode13.com
//
//Change log:
//4/27/2016 - Original file finalized
//
//Read an OBJ file and generate vertex values (position, texture coordinates, vertex normals) that can then be used on a WebGL-based web.
//
//Vertex data constants:
//VERTEX_POSITION - the x-, y-, and z-coordinates of each vertex
//TEXTURE_COORDINATE - the uv coordinates of each vertex
//VERTEX_NORMAL - the x-, y-, and z-values of the normal vector to each vertex
//
//Passing in one of the vertex data constants will cause this script to read the OBJ file you've specified and echo only the vertex data
//that you've specified.  This is useful because typically you are setting up separate buffers for each of these vertex data points, so you'll
//want to isolate these portions of the file and set up arrays for them separately in your client code.  If you are setting up interleaved 
//buffers then you'd have to call each of these functions separately and interleave the data in your client code.
//
//WARNING - This function assumes the passed OBJ file contains all three vertex elements (vertex positions, texture coordinates, and vertex normals).
//If your file does not contain all of those elements (even if you're not using them) it will produce errors when it tries to determine 
//which values to use for faces.  The good news is that you can just have your 3D software export these values and then ignore them when
//you're reading the file and rendering.  For example, if you have a mesh that you want to use without a texture, export the OBJ file from 
//your 3D software with texture coordinates (your software will have generated them even if you're not working with a texture), then ignore 
//them when building your vertex arrays.
//
//TRIANGLES ONLY - This function assumes that the mesh read from the OBJ file consists only of triangles.  If it consists of quads or any
//other polygon shape with more than three vertices, this function will only read the first three vertices of each polygon.
//
//Unindexed (or de-indexed as it were) - The output of this script assumes you are not using index buffers to refer to your vertices.  This means
//that some of the vertex data will be repeated.  For example, if you have a quad, its faces will be described as:
//
//1/2/3 - First triangle face, which is made up of vertices 1, 2, and 3
//2/4/3 - Second triangle face, which is made up of vertices 2, 4, and 3
//
//In a typical OBJ file each element of vertex information appears only once, so in our example above the position coordinates for vertex 3 won't be repeated
//in the OBJ file, even though they're used more than once to draw the quad.  This script addresses the issue of vertexes used more than once by repeating their //information in its output.  This means you don't need to (and in fact shouldn't) use a vertex index for the output; you can issue drawArray() calls 
//straight from the buffer(s) you create using this script.
//****************************

//vertex data constants
define("VERTEX_POSITION", 1);
define("TEXTURE_COORDINATE", 2);
define("VERTEX_NORMAL", 3);


//objToWebGL
//
//Read the passed OBJ file, extract the relevant vertex data from it, and echo the vertex data as array elements.  Then echo the
//vertex count so the caller can use that value in other WebGL-related functions.  The vertex count will be echoed as a Javascript
//variable declaration and that variable will be initialized with the vertex count.  Callers should not declare the variable in 
//their client code, but can refer to it in code that follows this function call.
//
//If this function encounters any errors it will echo them as Javascript comments so the developer can see them in the client code that 
//this generates.
//
//Parameters:
//$onjFilePath - Path to the OBJ file that contains the relavant mesh
//$vertexDataSpecifier - one of the three valid vertex data constants (see above).  If an invalid specifier is passed this function does nothing.
//$vertexCountVarName - the name of the variable that should be declared and assigned the count of vertices in the OBJ file.
function objToWebGL($objFilePath, $vertexDataSpecifier, $vertexCountVarName)
{
	//variables
	$lineElements;			//an array of the individual parts of a line in the file, typically after an explode() call
	$faceElements;			//faces are described using #/#/# notation, so after explode()ing that line, this array will hold arrays 
							//of vertex elements for each point making up a face.
	$ignoreLine;			//boolean flag for irrelevant lines
	$vertices = array();	//the array that contains the actual vertex data after it's read from the file and processed.  Arrays of 
							//the individual values are added to this array.
	$vertexCount = 0;		//number of vertices processed and echoed (this will count repeats)
	$distinctVertexCount = 0;	//actual number of unique vertices in the file (in which none are repeated)					
	$faces = array();		//array that contains the elements of the vertex array that make up each face.  Each element in this array is 
							//a three-element array that contains the element numbers of the vertices in the $vertices array that make 
							//up the face.
	$faceCount = 0;		//number of faces this script has processed
	$facesWritten = 0;	//number of faces whose data has been added to the output string
	$javascriptOutput;		//the final string for each line that will be echoed to the client as Javascript.
	
	$showedVertexWarning = FALSE;	//flag that prevents the vertex warning (not enough information) to be shown more than once
	$showedTriangleWarning = FALSE;	//flag that prevents the triangle warning (file contains faces with more than three vertices) from showing more than once.
	
	//read the file
	$fileContents = file($objFilePath);
	
	//if the file could be read...
	if($fileContents != FALSE)
	{
		foreach($fileContents as $fileLine)
		{
			//trim the newline character
			$fileLine = trim($fileLine);
			
			//see if the line is related to vertex position and, if it is, if the caller wants vertex position returned
			if(eregi("^v ", $fileLine) == TRUE && $vertexDataSpecifier == VERTEX_POSITION)
			{
				$lineElements = explode(" ", $fileLine);
				//make sure there are actually three elements (four including the leading "v") before reading the vertex position
				if(count($lineElements) == 4)
				{
					$vertices[$distinctVertexCount] = array($lineElements[1], $lineElements[2], $lineElements[3]);
					$distinctVertexCount++;
				}
			}
			//texture coordinates
			else if(eregi("^vt ", $fileLine) == TRUE && $vertexDataSpecifier == TEXTURE_COORDINATE)
			{
				$lineElements = explode(" ", $fileLine);
				//make sure there are actually two elements (three including the leading "vt") before reading the vertex position
				if(count($lineElements) == 3)
				{
					$vertices[$distinctVertexCount] = array($lineElements[1], $lineElements[2]);
					$distinctVertexCount++;
				}
			}
			//vertex normal
			else if(eregi("^vn ", $fileLine) == TRUE && $vertexDataSpecifier == VERTEX_NORMAL)
			{
				$lineElements = explode(" ", $fileLine);
				//make sure there are actually three elements (four including the leading "v") before reading the vertex position
				if(count($lineElements) == 4)
				{
					$vertices[$distinctVertexCount] = array($lineElements[1], $lineElements[2], $lineElements[3]);
					$distinctVertexCount++;
				}
			}
			//face determination
			else if(eregi("^f ", $fileLine) == TRUE)
			{
				$lineElements = explode(" ", $fileLine);
				//there should be three faces plus the 'f'
				if(count($lineElements) == 4)
				{
					//extract the face information from the line
					$faceElements[0] = explode("/", $lineElements[1]);	//first vertex
					$faceElements[1] = explode("/", $lineElements[2]);	//second vertex
					$faceElements[2] = explode("/", $lineElements[3]);	//third vertex
					
					//make sure the passed file has three pieces of information (positions, texture coordinates, and normals) per
					//face.
					if(count($faceElements[0]) == 3)
					{
						//depending on which type of data the caller wants, store the appropriate vertex elements from each face
						if($vertexDataSpecifier == VERTEX_POSITION)
						{
							//store the first number in the #/#/# series for each vertex.
							$faces[$faceCount] = array($faceElements[0][0], $faceElements[1][0], $faceElements[2][0]);
						}
						else if($vertexDataSpecifier == TEXTURE_COORDINATE)
						{
							//store the second number in the #/#/# series for each vertex.
							$faces[$faceCount] = array($faceElements[0][1], $faceElements[1][1], $faceElements[2][1]);
						}
						else if($vertexDataSpecifier == VERTEX_NORMAL)
						{
							//store the third number in the #/#/# series for each vertex.
							$faces[$faceCount] = array($faceElements[0][2], $faceElements[1][2], $faceElements[2][2]);
						}
						
						$faceCount++;
					}
					else
					{
						if($showedVertexWarning == FALSE)
						{
							$javascriptOutput = "//The vertices in the OBJ file " . $objFilePath . " do not contain all three necessary values (position, 
								texture coordinates, and vertex normals) per vertex.  Use your 3D software to re-export the OBJ file and 
								be sure to include all three of those vertex values in your file, then try this call again.";
							echo $javascriptOutput;
							$showedVertexWarning = TRUE;	//don't show this warning more than once.
						}
					}
				}
				else
				{
					if($showedTriangleWarning == FALSE)
					{
						$javascriptOutput = "//At least once face in the OBJ file has more than three vertices.  This function only operates
						on faces with three vertices, so any faces with more than three may be rendered incorrectly (vertices beyond three per
						face aren't read).";
						echo $javascriptOutput;
						$showedTriangleWarning = TRUE;	//don't show this warning more than once.
					}
				}//end of if face contains 3 vertices
			}//end of check for line value type
		}//end of for each line in the file
		
		//Now that the file has been read, write the vertex data to the client in array form
		$facesWritten = 0;
		$javascriptOutput = "[";
		foreach($faces as $face)
		{
			if($vertexDataSpecifier == TEXTURE_COORDINATE)
			{
				//two elements per vertex
				//first vertex of face
				$javascriptOutput = $javascriptOutput . $vertices[$face[0] - 1][0] . ", " . $vertices[$face[0] - 1][1] . ", \n";
				//second vertex of face
				$javascriptOutput = $javascriptOutput . $vertices[$face[1] - 1][0] . ", " . $vertices[$face[1] - 1][1] . ", \n";
				//third vertex of face
				$javascriptOutput = $javascriptOutput . $vertices[$face[2] - 1][0] . ", " . $vertices[$face[2] - 1][1];
			}
			else
			{
				//three elements per vertex
				//first vertex of face
				$javascriptOutput = $javascriptOutput . $vertices[$face[0] - 1][0] . ", " . $vertices[$face[0] - 1][1] . ", " . $vertices[$face[0] - 1][2] . ", \n";
				//second vertex of face
				$javascriptOutput = $javascriptOutput . $vertices[$face[1] - 1][0] . ", " . $vertices[$face[1] - 1][1] . ", " . $vertices[$face[1] - 1][2] . ", \n";
				//third vertex of face
				$javascriptOutput = $javascriptOutput . $vertices[$face[2] - 1][0] . ", " . $vertices[$face[2] - 1][1] . ", " . $vertices[$face[2] - 1][2];
			}
			
			$vertexCount = $vertexCount + 3;	//three vertices per face
			
			$facesWritten++;
			
			//unless this is the last face to be added, add another comma delimiter
			if($facesWritten < $faceCount)
			{
				$javascriptOutput = $javascriptOutput . ", \n";
			}
		}
		
		//terminate the array
		$javascriptOutput = $javascriptOutput . "];";
		
		//add the variable that contains the vertex count
		$javascriptOutput = $javascriptOutput . "\n" . "var " . $vertexCountVarName . " = " . $vertexCount . ";\n";
		
		//write the final string to the client's Javascript code
		echo $javascriptOutput;
		
		//useful for debugging
		/*
		echo "\nRAW ARRAY CONTENTS\n\n";
		print_r($vertices);
		print_r($faces);
		*/
	}
	else
	{
		//the passed file could not be read, so let the developer on the client side know
		$javascriptOutput = "//objToWebGL Error - The file '" . $objFilePath . "' could not be read.";
		echo $javascriptOutput;
	}
}
?>