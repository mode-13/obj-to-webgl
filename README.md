# obj-to-webgl
Reads vertex data from an OBJ file and produces Javascript data that can be used for WebGL meshes.

##Summary
Use this PHP function to read an OBJ file on your web server and echo the vertex data to your Javascript code.  It handles vertex position, texture coordinates, and vertex normals.  It even adds the vertex count to your code so you can use it (setting up VBOs, for example) without knowing it ahead of time.
###The files
**obj_to_webgl.php** - The function you will call is here.  Include() this file in your page.

**obj_to_webgl_test_page.php** - An example page so you can see how this works.  This has the PHP calls and no vertex data

**webgl_utils.js** - Some utility WebGL functions I use on the sample page.  Perspective matrix creation, etc.

**heart_webgl.obj** - The OBJ file I used on the sample web page.

**heart_webgl_texture.png** - The texture I used on the sample web page.


##How would one use this?
You include() the obj_to_webgl.php in the web page that will include your WebGL drawing code.  You do the rest of your WebGL setup and, when you're ready to specify your vertex data, you call this function to produce the vertex array data and tell you how many vertices there are.

So this code
```javascript
//mesh vertex positions
meshVertexPositionBuffer = glContext.createBuffer();
glContext.bindBuffer(glContext.ARRAY_BUFFER, meshVertexPositionBuffer);
	
//here's the array where you specify your vertex data.  This is where you call the PHP function
var vertices = <?php objToWebGL("my_3d_mesh.obj", VERTEX_POSITION, "vertexCount"); ?>
	
glContext.bufferData(glContext.ARRAY_BUFFER, new Float32Array(vertices), glContext.STATIC_DRAW);
heartVertexPositionBuffer.itemSize = 3;
heartVertexPositionBuffer.numItems = vertexCount; //the vertexCount variable is the third parameter you pass, and the
                                                  //PHP function will produce code to declare and populate it.
```
becomes
```javascript
//mesh vertex positions
meshVertexPositionBuffer = glContext.createBuffer();
glContext.bindBuffer(glContext.ARRAY_BUFFER, meshVertexPositionBuffer);
	
//here's the array where you specify your vertex data.  This is where you call the PHP function
var vertices = [0.000000, 1.007883, 0.408424, 
-0.176777, 1.000000, 0.402120, 
0.000000, -0.416068, -0.000000,
...
//[more vertex data from your OBJ file]
...
0.630065, 1.000000, -0.322297, 
0.756117, 1.302029, -0.161149];
var vertexCount = 384;  //the PHP function provides this declaration and count, so you can use it later in your code...
	
glContext.bufferData(glContext.ARRAY_BUFFER, new Float32Array(vertices), glContext.STATIC_DRAW);
heartVertexPositionBuffer.itemSize = 3;
heartVertexPositionBuffer.numItems = vertexCount; //...like right here!
```
To see the function in action, here is a link to my sample web page that uses it:

[http://mode13.com/obj_to_webgl_test_page.php](http://mode13.com/obj_to_webgl_test_page.php)

The original PHP file that still includes the PHP call (and none of the vertex data) is the file with the same name in this repository.


##Unindexed (or de-indexed, as it were) vertices
The output of this script assumes you are not using index buffers to refer to your vertices.  This means
that some of the vertex data will be repeated.  For example, if you have a quad, its faces will be described as:


1/2/3 - First triangle face, which is made up of vertices 1, 2, and 3

2/4/3 - Second triangle face, which is made up of vertices 2, 4, and 3


In a typical OBJ file each element of vertex information appears only once, so in our example above the position coordinates for vertex 3 won't be repeated in the OBJ file, even though they're used more than once to draw the quad.  This script addresses the issue of vertices used more than once by repeating their information in its output.  This means you don't need to (and in fact shouldn't) use a vertex index for the output; you can issue drawArray() calls straight from the buffer(s) you create using this script.


##OBJ File specifics
I wrote this as a utility to get meshes created in any major 3D program onto a WebGL page quickly.  As such I made some assumptions about files that bear mentioning.
###Triangles only
This function assumes all of the polygons in the OBJ mesh are triangles.  If the model you're exporting has faces composed of more than three vertices the extra vertices will not be read.  You can use your 3D modeling software's "Triangulate" functionality to convert your mesh to all triangles.
###Must contain all three pieces of vertex information (position, texture coords, normals)
Because I was working with model files that contained texture and lighting data, I wrote this to assume the specified file contained the position of each vertex, its texture coordinates, and its vertex normal.  Even if you're not using lighting and/or textures, you must export those values to your OBJ file along with your vertex position data in order for this to work properly.  In most 3D software packages you can tick a couple of extra boxes and have these data points exported.


##Thanks
I want to thank Giles Thomas for creating the WebGL tutorials at [learningwebgl.com](http://learningwebgl.com) and Tony Parisi for continuing to carry the torch for the site.  The tutorials made my transition from OpenGL programmer to WebGL programmer much easier.  In fact much of the code on my sample page is based on my going through those tutorials.

Thanks also to OpenGL.org and their OpenGL Helper library code.  With fixed function OpenGL going by the wayside I needed a way to create perspective projection matrices, and they provided the code that is behind those magical glFrustum calls of old.
