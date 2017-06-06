<?php
include '../../inc.comm.php';

$obj = load('tts_polly');
if(isset($_POST['text'])&&isset($_POST['voiceId'])) {
	$config = array('OutputFormat' => 'mp3',
			'SampleRate' => '22050',
			'Text' => $_POST['text'],
			'TextType' => 'text',
			'VoiceId' => $_POST['voiceId'],
	);
	
	$result = $obj->createSynthesizeSpeechUrl($config);
	
	$obj->saveStream($result);
	echo "<a href=".$result." target='_blank'>点击试听</a>";
}

?>

<html>
<head>
<title>Polly demo</title>
</head>
<body>
 <style>
        #input {
            min-width: 100px;
            max-width: 600px;
            margin: 0 auto;
            padding: 50px;
        }

        #input div {
            margin-bottom: 20px;
        }

        #text {
            width: 100%;
            height: 200px;
            display: block;
        }

        #submit {
            width: 100%;
        }
    </style>
    <form id="input" method="POST" action="">
        <div>
            <label for="voice">Select a voice:</label>
            <select id="voice" name="voiceId">
                <option value="">Choose a voice...</option>
                <option value="Joanna">Joanna, Female</option>
                <option value="Salli">Salli, Female</option>
                <option value="Kimberly">Kimberly, Female</option>
                <option value="Kendra">Kendra, Female</option>
                <option value="Ivy">Ivy, Female</option>
                <option value="Justin">Justin, Male</option>
                <option value="Joey">Joey, Male</option>
            </select>
        </div>
        <div>
            <label for="text">Text to read:</label>
            <textarea id="text" maxlength="1000" minlength="1" name="text"
                    placeholder="Type some text here..."></textarea>
        </div>
        <input type="submit" value="确定" id="submit" />
    </form>
</body>

</html>