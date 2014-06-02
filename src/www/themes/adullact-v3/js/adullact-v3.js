function admin_window(adminurl) {
	AdminWin = window.open( adminurl, 'AdminWindow','scrollbars=yes,resizable=yes, toolbar=yes, height=400, width=400, top=2, left=2');
	AdminWin.focus();
}

function help_window(helpurl) {
	HelpWin = window.open( helpurl,'HelpWindow','scrollbars=yes,resizable=yes,toolbar=no,height=400,width=600');
}

function imgOver(imgName) {
	if ( document.images ) {
		document[imgName].src=eval(imgName + "hover.src");
	}
}

function imgOff(imgName) {
	if ( document.images ) {
		document[imgName].src=eval(imgName + "off.src");
	}
}

if ( document.images ) {
	logooff=new Image();
	logooff.src="/themes/adullact-v3/images/logo.png";

	logohover=new Image();
	logohover.src="/themes/adullact-v3/images/logohover.png";
}