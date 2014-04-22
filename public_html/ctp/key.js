var key=new Object();
var keyConst=new Array();
keyConst[38]="up";
keyConst[40]="down";
keyConst[37]="left";
keyConst[39]="right";
keyConst[32]="space";
keyConst[17]="ctrl";
keyConst[16]="shift";
keyConst[13]="enter";
keyConst[18]="alt";
keyConst[65]="a";
keyConst[83]="s";
keyConst[68]="d";
keyConst[87]="w";

function Keyboard_Init()
{
	var i;
	for(i in keyConst)
	{
		key[i]=false;
	}
	document.onkeydown=function(event){
		Keyboard_KeyDown(event);
		if ((event.which >= 37) || (event.which <= 40)) {
			return false;
		}
	};
	document.onkeyup=function(event){
		Keyboard_KeyUp(event);
		if ((event.which >= 37) || (event.which <= 40)) {
			return false;
		}
	}
}

function Keyboard_KeyDown(event)
{
	var i;
	var kc=Keyboard_KeyCode(event);
	if (kc in keyConst)
	{
		i=keyConst[kc];
		key[i]=true;
	}
}

function Keyboard_KeyUp(event)
{
	var i;
	var kc=Keyboard_KeyCode(event);
	if (kc in keyConst)
	{
		i=keyConst[kc];
		key[i]=false;
	}
}

function Keyboard_KeyCode(e)
{
	if(document.all)
		return  e.keyCode;
	else if(document.getElementById)
		return (e.keyCode)? e.keyCode: e.charCode;
	else if(document.layers)
		return  e.which;
}