<?php
require "vendor/autoload.php";
use Imagine\Imagick\Imagine;
use Imagine\Imagick\Image;
use Imagine\Image\BoxInterface;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImageLibraryProcessor{
	static $sLibraryPath;

	public static function run($sLibraryPath){
		self::$sLibraryPath = realpath($sLibraryPath);
		$action		= $_GET['action'];
		$aSize		= explode('x',$_GET['size']);
		$filename	= $_GET['file'];
		$type		= $_GET['type'];
		$path		= $_GET['path'];
		$arg		= array_key_exists('arg',$_GET)?$_GET['arg']:null;
		$sMethod	= 'action'.ucfirst($action);
		$sFullfilename = realpath(self::$sLibraryPath.'/'.$filename);
		if (!file_exists($sFullfilename)){
			header('HTTP/1.0 404 Picture Not Found');
			die();
		}

		if (substr($sFullfilename,0,strlen(self::$sLibraryPath))!=self::$sLibraryPath){
			header('HTTP/1.0 403 Not allowed');
			die();
		}
		if(in_array($sMethod, get_class_methods('ImageLibraryProcessor'))){
			$imagine = new Imagine();
			/** @var $image Image */
			$image = $imagine->open($sFullfilename);
			$image = self::$sMethod($image,new Box($aSize[0], $aSize[0]),$arg);
			self::httpRender($image,$type,$path,$filename);
		}
	}

	private function actionResize(Image $image,BoxInterface $size,$arg){
		return $image->thumbnail($size, ImageInterface::THUMBNAIL_INSET);
	}

	private function httpRender(ImageInterface $image,$type,$path,$filename){
		$aPaths = explode('/',$path.'/'.$filename);
		array_pop($aPaths);
		$sExportPath =  implode('/',$aPaths);
		$fs = new Filesystem();
		if(!$fs->exists(self::$sLibraryPath.'/'.$sExportPath)){
			$fs->mkdir(self::$sLibraryPath.'/'.$sExportPath);
		}
		$image->save(self::$sLibraryPath.'/'.$sExportPath.'/'.$filename);
		$image->show($type);
	}
}

ImageLibraryProcessor::run(realpath(dirname(__FILE__).'/../library/'));
