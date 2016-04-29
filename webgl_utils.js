//*******************
//webgl_utils.js
//
//Jon Hellebuyck
//mode13.com
//
//Change log:
//4/28/2016 - Initial file contents completed
//
//A few utility functions that are helpful when using WebGL.
//
//The glhPerspectivef2 and glhFrustrumf2 code are straight from the OpenGL.org site and were originally part of the OpenGL Helper library: //https://sourceforge.net/projects/glhlib


//glhPerspectivef2
//
//return a perspective projection matrix based on the passed parameters
//fovy - field of view angle, in degrees, in any y-direction
//aspect - aspect ratio that determines the field of view in the x-direction: ratio of width(x) to height(y)
//nearZ - clipping plane for nearest Z
//farZ - clipping plane for farthest Z
function glhPerspectivef2(fovyInDegrees, aspectRatio, zNear, zFar)
{
    var ymax = zNear * Math.tan(fovyInDegrees * Math.PI / 360.0);
    var xmax = ymax * aspectRatio;
    return glhFrustumf2(-xmax, xmax, -ymax, ymax, zNear, zFar);
}

//glhFrustumf2
//
//Utility function used by glhPerspectivef2 (see above) to create
//a perspective projection matrix
function glhFrustumf2(left, right, bottom, top, zNear, zFar)
{
    var temp = 2.0 * zNear;
    var temp2 = right - left;
    var temp3 = top - bottom;
    var temp4 = zFar - zNear;
	var matrix = new Array();
    matrix[0] = temp / temp2;
    matrix[1] = 0.0;
    matrix[2] = 0.0;
    matrix[3] = 0.0;
    matrix[4] = 0.0;
    matrix[5] = temp / temp3;
    matrix[6] = 0.0;
    matrix[7] = 0.0;
    matrix[8] = (right + left) / temp2;
    matrix[9] = (top + bottom) / temp3;
    matrix[10] = (-zFar - zNear) / temp4;
    matrix[11] = -1.0;
    matrix[12] = 0.0;
    matrix[13] = 0.0;
    matrix[14] = (-temp * zFar) / temp4;
    matrix[15] = 0.0;
	
	return matrix;
}

//glTranslate
//
//Returns a 4x4 matrix that can be used to translate a mesh by the passed amounts
function glTranslate(x, y, z)
{
	var translateMatrix = [
		1, 0, 0, 0, 
		0, 1, 0, 0,
		0, 0, 1, 0, 
		x, y, z, 1
		];
		
	return translateMatrix;
}

//glRotateY
//
//Returns a 4x4 matrix that can be used to rotate a mesh about its y-axis by the 
//passed number of radians
function glRotateY(radians)
{
	var rotateYMatrix = [
		Math.cos(radians), 0, -Math.sin(radians), 0, 
		0, 1, 0, 0,
		Math.sin(radians), 0, Math.cos(radians), 0, 
		0, 0, 0, 1
		];
		
	return rotateYMatrix;
}

//glIdentityMatrix
//
//Returns a 4x4 identity matrix
function glIdentityMatrix()
{
	var identityMatrix = [
		1, 0, 0, 0, 
		0, 1, 0, 0,
		0, 0, 1, 0, 
		0, 0, 0, 1
		];
		
	return identityMatrix;
}

//multiplyMatrices4x4
//
//Multiplies (dot product) the first matrix by the second and returns the result
function multiplyMatrices4x4(matrix1, matrix2)
{
	var outputMatrix = new Array();
	
	outputMatrix[0] = (matrix1[0] * matrix2[0]) + (matrix1[1] * matrix2[4]) + (matrix1[2] * matrix2[8]) + (matrix1[3] * matrix2[12]);
	outputMatrix[1] = (matrix1[0] * matrix2[1]) + (matrix1[1] * matrix2[5]) + (matrix1[2] * matrix2[9]) + (matrix1[3] * matrix2[13]);
	outputMatrix[2] = (matrix1[0] * matrix2[2]) + (matrix1[1] * matrix2[6]) + (matrix1[2] * matrix2[10]) + (matrix1[3] * matrix2[14]);
	outputMatrix[3] = (matrix1[0] * matrix2[3]) + (matrix1[1] * matrix2[7]) + (matrix1[2] * matrix2[11]) + (matrix1[3] * matrix2[15]);
	outputMatrix[4] = (matrix1[4] * matrix2[0]) + (matrix1[5] * matrix2[4]) + (matrix1[6] * matrix2[8]) + (matrix1[7] * matrix2[12]);
	outputMatrix[5] = (matrix1[4] * matrix2[1]) + (matrix1[5] * matrix2[5]) + (matrix1[6] * matrix2[9]) + (matrix1[7] * matrix2[13]);
	outputMatrix[6] = (matrix1[4] * matrix2[2]) + (matrix1[5] * matrix2[6]) + (matrix1[6] * matrix2[10]) + (matrix1[7] * matrix2[14]);
	outputMatrix[7] = (matrix1[4] * matrix2[3]) + (matrix1[5] * matrix2[7]) + (matrix1[6] * matrix2[11]) + (matrix1[7] * matrix2[15]);
	outputMatrix[8] = (matrix1[8] * matrix2[0]) + (matrix1[9] * matrix2[4]) + (matrix1[10] * matrix2[8]) + (matrix1[11] * matrix2[12]);
	outputMatrix[9] = (matrix1[8] * matrix2[1]) + (matrix1[9] * matrix2[5]) + (matrix1[10] * matrix2[9]) + (matrix1[11] * matrix2[13]);
	outputMatrix[10] = (matrix1[8] * matrix2[2]) + (matrix1[9] * matrix2[6]) + (matrix1[10] * matrix2[10]) + (matrix1[11] * matrix2[14]);
	outputMatrix[11] = (matrix1[8] * matrix2[3]) + (matrix1[9] * matrix2[7]) + (matrix1[10] * matrix2[11]) + (matrix1[11] * matrix2[15]);
	outputMatrix[12] = (matrix1[12] * matrix2[0]) + (matrix1[13] * matrix2[4]) + (matrix1[14] * matrix2[8]) + (matrix1[15] * matrix2[12]);
	outputMatrix[13] = (matrix1[12] * matrix2[1]) + (matrix1[13] * matrix2[5]) + (matrix1[14] * matrix2[9]) + (matrix1[15] * matrix2[13]);
	outputMatrix[14] = (matrix1[12] * matrix2[2]) + (matrix1[13] * matrix2[6]) + (matrix1[14] * matrix2[10]) + (matrix1[15] * matrix2[14]);
	outputMatrix[15] = (matrix1[12] * matrix2[3]) + (matrix1[13] * matrix2[7]) + (matrix1[14] * matrix2[11]) + (matrix1[15] * matrix2[15]);
	
	return outputMatrix;
}