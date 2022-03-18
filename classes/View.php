<?php

class View {

    function getHtml($html) {

       return '
			<html>
			<head>
				<link type="text/css" rel="stylesheet" href="css/styles.css">
			</head>
			<body>
				' . $html . '
			</body>
			</html>';

    }


}