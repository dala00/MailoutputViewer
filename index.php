<?php
mb_internal_encoding('UTF-8');
$dir = '../../mailoutput';

if (!empty($_POST['delete'])) {
	unlink("$dir/{$_POST['delete']}");
	exit;
}

$files = scandir($dir);
$files = array_filter($files, function ($file) {
	return preg_match('/\.txt$/', $file);
});
$mails = array();
foreach ($files as $file) {
	$path = "$dir/$file";
	$lines = file($path);
	$isBody = false;
	$parts = explode('.', $file);
	$mail = array(
		'id' => $parts[0],
		'filename' => $file,
		'modified' => date('Y-m-d H:i:s', filemtime($path)),
		'headers' => array(),
		'body' => '',
	);
	foreach ($lines as $line) {
		if ($isBody) {
			$line = mb_convert_encoding($line, 'UTF-8', 'ISO-2022-JP');
			$mail['body'] .= $line;
		} else {
			if ($line = trim($line)) {
				list($field, $value) = explode(':', $line);
				$field = strtolower(trim($field));
				$value = trim($value);
				if ($field == 'subject') {
					mb_internal_encoding('ISO-2022-JP');
					$value = mb_decode_mimeheader($value);
					$value = mb_convert_encoding($value, 'UTF-8', 'ISO-2022-JP');
					mb_internal_encoding('UTF-8');
				}
				$mail['headers'][$field] = $value;
			} else {
				$isBody = true;
			}
		}
	}
	$mails[$mail['id']] = $mail;
}
$mails = array_reverse($mails);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>mail output viewer</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<style type="text/css">  
<!-- /*css*/
* {
//	font-size: 12px;
}
-->
</style>
<script type="text/javascript"> 
<!-- //javascript
var mails = <?php echo json_encode($mails); ?>;
var currentFilename = '';

$(document).ready(function() {
	$('#deleteButton').click(function() {
		$.post('.', {delete:encodeURIComponent(mails[currentFilename].filename)}, function(result) {
			if (result == '') {
				$('#tr' + currentFilename).remove();
				$('#mailbody').html('');
				delete mails[currentFilename];
				for (var filename in mails) {
					showMail(filename);
					break;
				}
			}
		});
	});
});

function showMail(filename) {
	var body = '';
	currentFilename = filename;
	
	$('#list tr').removeClass('active');
	$('#tr' + filename).addClass('active');
	
	body += mails[filename].body;
	body = body.replace(/\n/g, '<br />')
	$('#mailbody').html(body);
	$("html,body").animate({scrollTop:0});
}
//-->
</script>

</head>
<body>

<div class="container-fluid">
  <div class="row">
    <div class="col-xs-3">
    	<table class="table" id="list">
<?php foreach ($mails as $mail) { ?>
	<tr id="tr<?php echo $mail['id']; ?>">
	<td>
	<a href="#" onclick="showMail('<?php echo $mail['id']; ?>'); return false;"><?php echo $mail['modified']; ?> <?php echo $mail['headers']['subject']; ?></a>
	</td>
	</tr>
<?php } ?>
    	</table>
    </div>
    <div id="main" class="col-xs-9">
    <div style="text-align:right;">
    	<button class="btn" id="deleteButton">Delete</button>
    </div>
    <div id="mailbody">
	    main
	</div>
    </div>
  </div>
</div>


</body>
</html>