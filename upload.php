<?php
header('Content-Type: text/html; charset=GBK');

$inputName='filedata';//���ļ���name
$attachDir='upload';//�ϴ��ļ�����·������β��Ҫ��/
$dirType=1;//1:�������Ŀ¼ 2:���´���Ŀ¼ 3:����չ����Ŀ¼  ����ʹ�ð����
$maxAttachSize=2097152;//����ϴ���С��Ĭ����2M
$upExt='txt,rar,zip,jpg,jpeg,gif,png,swf,wmv,avi,wma,mp3,mid';//�ϴ���չ��
$msgType=2;//�����ϴ������ĸ�ʽ��1��ֻ����url��2�����ز�������
$immediate=isset($_GET['immediate'])?$_GET['immediate']:0;//�����ϴ�ģʽ����Ϊ��ʾ��
ini_set('date.timezone','Asia/Shanghai');//ʱ��

$err = "";
$msg = "''";
$tempPath=$attachDir.'/'.date("YmdHis").mt_rand(10000,99999).'.tmp';
$localName='';

if(isset($_SERVER['HTTP_CONTENT_DISPOSITION'])&&preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i',$_SERVER['HTTP_CONTENT_DISPOSITION'],$info)){//HTML5�ϴ�
	file_put_contents($tempPath,file_get_contents("php://input"));
	$localName=urldecode($info[2]);
}
else{//��׼��ʽ�ϴ�
	$upfile=@$_FILES[$inputName];
	if(!isset($upfile))$err='�ļ����name����';
	elseif(!empty($upfile['error'])){
		switch($upfile['error'])
		{
			case '1':
				$err = '�ļ���С������php.ini�����upload_max_filesizeֵ';
				break;
			case '2':
				$err = '�ļ���С������HTML�����MAX_FILE_SIZEֵ';
				break;
			case '3':
				$err = '�ļ��ϴ�����ȫ';
				break;
			case '4':
				$err = '���ļ��ϴ�';
				break;
			case '6':
				$err = 'ȱ����ʱ�ļ���';
				break;
			case '7':
				$err = 'д�ļ�ʧ��';
				break;
			case '8':
				$err = '�ϴ���������չ�ж�';
				break;
			case '999':
			default:
				$err = '����Ч�������';
		}
	}
	elseif(empty($upfile['tmp_name']) || $upfile['tmp_name'] == 'none')$err = '���ļ��ϴ�';
	else{
		move_uploaded_file($upfile['tmp_name'],$tempPath);
		$localName=$upfile['name'];
	}
}

if($err==''){
	$fileInfo=pathinfo($localName);
	$extension=$fileInfo['extension'];
	if(preg_match('/^('.str_replace(',','|',$upExt).')$/i',$extension))
	{
		$bytes=filesize($tempPath);
		if($bytes > $maxAttachSize)$err='�벻Ҫ�ϴ���С����'.formatBytes($maxAttachSize).'���ļ�';
		else
		{
			switch($dirType)
			{
				case 1: $attachSubDir = 'day_'.date('ymd'); break;
				case 2: $attachSubDir = 'month_'.date('ym'); break;
				case 3: $attachSubDir = 'ext_'.$extension; break;
			}
			$attachDir = $attachDir.'/'.$attachSubDir;
			if(!is_dir($attachDir))
			{
				@mkdir($attachDir, 0777);
				@fclose(fopen($attachDir.'/index.htm', 'w'));
			}
			PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
			$newFilename=date("YmdHis").mt_rand(1000,9999).'.'.$extension;
			$targetPath = $attachDir.'/'.$newFilename;
			
			rename($tempPath,$targetPath);
			@chmod($targetPath,0755);
			$targetPath=jsonString($targetPath);
			if($immediate=='1')$targetPath='!'.$targetPath;
			if($msgType==1)$msg="'$targetPath'";
			else $msg="{'url':'".$targetPath."','localname':'".jsonString($localName)."','id':'1'}";//id�����̶����䣬������ʾ��ʵ����Ŀ�п��������ݿ�ID
		}
	}
	else $err='�ϴ��ļ���չ������Ϊ��'.$upExt;

	@unlink($tempPath);
}

echo "{'err':'".jsonString($err)."','msg':".$msg."}";


function jsonString($str)
{
	return preg_replace("/([\\\\\/'])/",'\\\$1',$str);
}
function formatBytes($bytes) {
	if($bytes >= 1073741824) {
		$bytes = round($bytes / 1073741824 * 100) / 100 . 'GB';
	} elseif($bytes >= 1048576) {
		$bytes = round($bytes / 1048576 * 100) / 100 . 'MB';
	} elseif($bytes >= 1024) {
		$bytes = round($bytes / 1024 * 100) / 100 . 'KB';
	} else {
		$bytes = $bytes . 'Bytes';
	}
	return $bytes;
}
?>