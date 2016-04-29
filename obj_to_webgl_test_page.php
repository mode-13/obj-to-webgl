<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>WebGL Demonstration - OBJ File</title>
<?php
include("obj_to_webgl.php");
?>
<script id="shader-fs" type="x-shader/x-fragment">
    precision mediump float;
	
	varying vec2 vTextureCoord;
	
	uniform sampler2D uSampler;

    void main(void) {		
		//use texture image and coordinates to determine color
		gl_FragColor = texture2D(uSampler, vec2(vTextureCoord.s, vTextureCoord.t));
    }
</script>

<script id="shader-vs" type="x-shader/x-vertex">
    attribute vec3 aVertexPosition;
	attribute vec2 aTextureCoord;

    uniform mat4 uMVMatrix;
    uniform mat4 uPMatrix;
	
	varying vec2 vTextureCoord;

    void main(void) {
        gl_Position = uPMatrix * uMVMatrix * vec4(aVertexPosition, 1.0);
		vTextureCoord = aTextureCoord;
    }
</script>

<script type="text/javascript" src="webgl_utils.js"></script>

<script type="text/javascript">

var glContext;
var glCanvas;
var mvMatrix;
var pMatrix;
var heartScreenY = -0.50;
var rotateYRadians = 0;

window.addEventListener("load", pageLoaded, false);

function pageLoaded()
{
	glCanvas = document.getElementById("webgl");
	glContext = glCanvas.getContext("experimental-webgl");
	
	if(glContext != null)
	{
		glContext.viewportWidth = glCanvas.width;
		glContext.viewportHeight = glCanvas.height;
		glContext.clearColor(0.0, 0.0, 0.0, 1.0);
		glContext.enable(glContext.DEPTH_TEST);
		
		//create a perspective projection matrix
		pMatrix = glhPerspectivef2(45, glContext.viewportWidth / glContext.viewportHeight, 0.1, 100.0);
		
		//set the shaders, vertex buffers, and textures up
		initShaders();
		initBuffers();
		initTextures();
		
		//set up a timer that calls drawscene
		window.setInterval(updateAndDrawScene, 1000/30);
	}
	else
	{
		alert("no Web GL canvas context available");
	}
}

function updateAndDrawScene()
{
	drawScene();
}

//global needed for a shader program
var shaderProgram;

function initShaders()
{
	//read and compile the shaders
	var fragmentShader = getShader(glContext, "shader-fs");
	var vertexShader = getShader(glContext, "shader-vs");

	//assemble the shaders into a program and link it
	shaderProgram = glContext.createProgram();
	glContext.attachShader(shaderProgram, vertexShader);
	glContext.attachShader(shaderProgram, fragmentShader);
	glContext.linkProgram(shaderProgram);

	if (!glContext.getProgramParameter(shaderProgram, glContext.LINK_STATUS)) {
		alert("Could not initialise shaders");
	}

	glContext.useProgram(shaderProgram);

	//get the shader location that corresponds to the vertex position variable
	shaderProgram.vertexPositionAttribute = glContext.getAttribLocation(shaderProgram, "aVertexPosition");
	glContext.enableVertexAttribArray(shaderProgram.vertexPositionAttribute);
	
	//get the shader location that corresponds to the texture coordinate variable
	shaderProgram.textureCoordAttribute = glContext.getAttribLocation(shaderProgram, "aTextureCoord");
    glContext.enableVertexAttribArray(shaderProgram.textureCoordAttribute);

	//get the locations that should be used to pass uniforms to the shaders
	shaderProgram.pMatrixUniform = glContext.getUniformLocation(shaderProgram, "uPMatrix");
	shaderProgram.mvMatrixUniform = glContext.getUniformLocation(shaderProgram, "uMVMatrix");
	shaderProgram.samplerUniform = glContext.getUniformLocation(shaderProgram, "uSampler");
}


//Set the shader uniform values, which in this case are the projection matrix and 
//model view matrix
function setMatrixUniforms() {
	glContext.uniformMatrix4fv(shaderProgram.pMatrixUniform, false, pMatrix);
	glContext.uniformMatrix4fv(shaderProgram.mvMatrixUniform, false, mvMatrix);
}

var heartVertexPositionBuffer;
var heartVertexColorBuffer;

function initBuffers()
{
	//heart vertex positions
	heartVertexPositionBuffer = glContext.createBuffer();
	glContext.bindBuffer(glContext.ARRAY_BUFFER, heartVertexPositionBuffer);

	var vertices = <?php objToWebGL("heart_webgl.obj", VERTEX_POSITION, "vertexCount"); ?>
	glContext.bufferData(glContext.ARRAY_BUFFER, new Float32Array(vertices), glContext.STATIC_DRAW);
	heartVertexPositionBuffer.itemSize = 3;
	//heartVertexPositionBuffer.numItems = 3;
	heartVertexPositionBuffer.numItems = vertexCount;
	
	//heart texture coordinates
	heartVertexTextureCoordBuffer = glContext.createBuffer();
    glContext.bindBuffer(glContext.ARRAY_BUFFER, heartVertexTextureCoordBuffer);

	var textureCoords = <?php objToWebGL("heart_webgl.obj", TEXTURE_COORDINATE, "textureCoordCount"); ?>
    glContext.bufferData(glContext.ARRAY_BUFFER, new Float32Array(textureCoords), glContext.STATIC_DRAW);
    heartVertexTextureCoordBuffer.itemSize = 2;
    //heartVertexTextureCoordBuffer.numItems = 3;
	heartVertexTextureCoordBuffer.numItems = textureCoordCount;
}

function getShader(glContext, shaderElementId) {

	//get the shader code from the <script> element
	var success = true;	//flag for what should be returned
	var shaderScript = document.getElementById(shaderElementId);
	if (shaderScript != null) {
		var shaderCode = "";
		var shaderCodeNode = shaderScript.firstChild;
		//make sure the code node is a text-type node
		if (shaderCodeNode.nodeType == 3) {
			shaderCode += shaderCodeNode.textContent;
		}
	}
	else
	{
		success = false;
	}
	
	//create the actual shader
	var shader;
	if (shaderScript.type == "x-shader/x-fragment")
	{
		shader = glContext.createShader(glContext.FRAGMENT_SHADER);
	} 
	else if (shaderScript.type == "x-shader/x-vertex")
	{
		shader = glContext.createShader(glContext.VERTEX_SHADER);
	}
	else
	{
		success = false;
	}

	glContext.shaderSource(shader, shaderCode);
	glContext.compileShader(shader);

	if (glContext.getShaderParameter(shader, glContext.COMPILE_STATUS) == false || glContext.getShaderParameter(shader, glContext.COMPILE_STATUS) == null) {
		success = false;
	}

	//if everything went well, return the shader.  If not, return null
	if(success == true)
	{
		return shader;
	}
	else
	{
		return null;
	}
}

var testTexture;

//Load the sample texture
function initTextures()
{
	testTexture = glContext.createTexture();
	testTexture.image = new Image();
	testTexture.image.onload = function() {
      handleLoadedTexture(testTexture)
    }

    testTexture.image.src = "heart_webgl_texture.png";
}

function handleLoadedTexture(textureIn)
{
	glContext.bindTexture(glContext.TEXTURE_2D, textureIn);
    glContext.pixelStorei(glContext.UNPACK_FLIP_Y_WEBGL, true);
    glContext.texImage2D(glContext.TEXTURE_2D, 0, glContext.RGBA, glContext.RGBA, glContext.UNSIGNED_BYTE, textureIn.image);
    glContext.texParameteri(glContext.TEXTURE_2D, glContext.TEXTURE_MAG_FILTER, glContext.NEAREST);
    glContext.texParameteri(glContext.TEXTURE_2D, glContext.TEXTURE_MIN_FILTER, glContext.NEAREST);
    glContext.bindTexture(glContext.TEXTURE_2D, null);
}

function drawScene()
{
	glContext.viewport(0, 0, glContext.viewportWidth, glContext.viewportHeight);
	glContext.clear(glContext.COLOR_BUFFER_BIT | glContext.DEPTH_BUFFER_BIT);

	rotateYRadians += 0.025;
	if(rotateYRadians >= (2 * Math.PI))
	{
		rotateYRadians = rotateYRadians - (2 * Math.PI);
	}
	
	//rotate then translate
	mvMatrix = multiplyMatrices4x4(glRotateY(rotateYRadians), glTranslate(-0.0, heartScreenY, -5.0));
	
	//specify the vertex position buffer and specify the shader variable that will use it
	glContext.bindBuffer(glContext.ARRAY_BUFFER, heartVertexPositionBuffer);
	glContext.vertexAttribPointer(shaderProgram.vertexPositionAttribute, heartVertexPositionBuffer.itemSize, glContext.FLOAT, false, 0, 0);
	
	//specify the texture coordinate buffer and specify the shader variable that will use it
	glContext.bindBuffer(glContext.ARRAY_BUFFER, heartVertexTextureCoordBuffer);
    glContext.vertexAttribPointer(shaderProgram.textureCoordAttribute, heartVertexTextureCoordBuffer.itemSize, glContext.FLOAT, false, 0, 0);
	glContext.activeTexture(glContext.TEXTURE0);
    glContext.bindTexture(glContext.TEXTURE_2D, testTexture);
    glContext.uniform1i(shaderProgram.samplerUniform, 0);
	setMatrixUniforms();
	glContext.drawArrays(glContext.TRIANGLES, 0, heartVertexPositionBuffer.numItems);
}

</script>

</head>

<body>
<canvas id="webgl" width="480" height="320">This browser does not support HTML5 Canvas
</canvas>
<div style="font-family:Geneva, Arial, Helvetica, sans-serif;">
<br />Textured heart mesh (low-polygon to keep the vertex data reasonable).<br /> <br /><br />Thanks to Giles Thomas for his WebGL tutorials at:<br /><br />  <a href="http://learningwebgl.com">learningwebgl.com</a>.  <br /> <br />Very helpful in putting this together.
</div>
<?php
//include("obj_to_webgl.php");
//objToWebGL("quad_lit.obj", VERTEX_POSITION, "vertexCount");
?>
</body>
</html>
