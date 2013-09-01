<!-- framebuster for exploding links out of the Canvas wrapper -->
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