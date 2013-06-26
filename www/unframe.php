<html>
	<head>
		<script><!--
		function frameBustingRedirect(url) {
			top.location.replace(url);
		}
		--></script>
	</head>
	<body onload="frameBustingRedirect('<?= $_REQUEST["url"] ?>');">
	</body>
</html>