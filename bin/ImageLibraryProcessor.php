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
	static $aTypeExtensions = [
		'jpg' =>'jpg',
		'gif' =>'gif',
		'png' =>'png',
		'jpeg'=>'jpg'
	];

	public static function run($sLibraryPath){
		self::$sLibraryPath = realpath($sLibraryPath);
		$path		= $_GET['path'];
		$filename	= $_GET['file'];
		$aSize		= explode('x',$_GET['size']);
		$arg		= trim($_GET['arg'])==''?[]:explode('/',$_GET['arg']);
		$type		= self::$aTypeExtensions[strtolower($_GET['type'])];
		$sMethod	= 'action'.ucfirst($_GET['action']);
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
			$image = self::$sMethod($image,new Box($aSize[0], $aSize[1]),$arg);
			self::httpRender($image,$type,$path,$filename);
		}
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

	private function checkParams(array $aParams,BoxInterface $size,array $aTemplate){
		$args = [];
		$aErrors=[];
		if($size->getWidth() >2048){
			$aErrors[]='too large';
		}
		if($size->getHeight() >2048){
			$aErrors[]='too high';
		}
		foreach($aTemplate as $argTtpl){
			$args[$argTtpl['name']]=$argTtpl['default'];
			foreach($aParams as $k=>$subArg){
				$rgx='!'.$argTtpl['rgx'].'!';
				$match=[];
				if (preg_match($rgx,$subArg,$match) && $args[$argTtpl['name']] = preg_replace($rgx,$argTtpl['rpl'],$subArg)){
					unset($aParams[$k]);
					continue;
				}
			}
		}

		if(count($aParams)>0){
			$aErrors[] = 'Unknown parameters : '.implode(',',$aParams);
		}

		if(count($aErrors)>0){
			$sError=implode('/',$aErrors);
			header('HTTP/1.0 500 '.$sError);
			die('<h1>'.$sError.'</h1>');
		}
		return $args;
	}

	/**
	 *
	 * @param Image $image
	 * @param BoxInterface $size
	 * @param string $arg
	 * @return \Imagine\Imagick\Image|\Imagine\Image\ImageInterface
	 */
	private function actionThumbnail(Image $image,BoxInterface $size,$args=[]){
		$args=self::checkParams($args,$size,[
			[
				'name'=>'mode',
				'rgx'=>'mode(I|O)',
				'rpl'=>'$1',
				'default'=>'I'
			]
		]);

		switch ($args['mode']){
			case 'I':
				$mode=ImageInterface::THUMBNAIL_INSET;
			break;
			case 'O':
				$mode=ImageInterface::THUMBNAIL_OUTBOUND;
			break;
		}
		return $image->thumbnail($size,$mode);
	}

}

ImageLibraryProcessor::run(realpath(dirname(__FILE__).'/../library/'));
