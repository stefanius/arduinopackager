<?php 
/*********************************************/
/*****************    INIT   *****************/
/*********************************************/
require_once 'arguments.php';
$required_args = array('flags' => array('p'));
//$cli = checkarguments($argv, $required_args);
// '/home/stefanius/git/arduino/Boksklok/Boksklok.ino'
$args = parsearguments ( $argv );



/**************************************************/
/*****************  DECLARATIONS  *****************/
/**************************************************/
$filename=false;
$path = false;
$libraries = array();
$projects = array();


/**************************************************/
/*****************  CHECK PARAMS  *****************/
/**************************************************/

if(isset($args['flags'][0]) && isset($args['commands'][0])){
	if($args['flags'][0]=='p'){
		$path = $args['commands'][0];
	}elseif($args['flags'][0]=='f'){
		$filename = $args['commands'][0];
	}	
}

if($filename===false && $path===false){
	echo "\n\n\n*************************************************************\n\n";
	echo "No path or filename is found. Run this script with: \n";
	echo "    -f /path/to/file/and/filename.c\n";
	echo "  OR  \n";
	echo "    -p /path/to/arduino/projects/root \n";
	echo "\n*************************************************************\n";
	exit(1);
}



/**********************************************/
/*****************  EXECUTE  ******************/
/**********************************************/

if($filename !== false){
	$projects = parse(array($filename));
}elseif($path !==false){
	$paths = substractDirectories($path, array('examples', 'packages', 'libraries'));
	$filenames = array();
	
	foreach($paths as $path){
		$exploded = explode('/', $path);
		$exploded[] = $exploded[count($exploded)-1].'.ino';
		$filename = implode('/', $exploded);
		$filenames[] = $filename;
	}
	$projects = parse($filenames);
}

foreach($projects as $project){
	writeLibFiles($project);
}

/*********************************************/
/***************** FUNCTIONS *****************/
/*********************************************/

function writeLibFiles($project)
{
	$filedata = '';
	
	foreach($project['libraries'] as $state => $libs){
		$filename = 'librairies_'.$state.'.packager';
		
		foreach($libs as $lib){
			$filedata.=$lib['name']."\n";
		}
		file_put_contents($project['projectpath'].'/'.$filename, $filedata);
	}
}

function parse($filenames = array() )
{
	$projects = array();
	foreach($filenames as $filename){
		$p = explode('/', $filename);
		$project = $p[count($p)-2];
		$libraries = extractLibrariesFromFile ($filename);
		$projects[$project]['libraries'] = $libraries;
		$projects[$project]['filename'] =$filename;
		$projects[$project]['projectname'] =$project;
		
		unset($p[count($p)-1]);
		$p = implode('/', $p);
		$projects[$project]['projectpath'] =$p;		
	}
	return $projects;
}

function substractDirectories($path, $ignores = array())
{
	$paths = array();

	if(!is_array($ignores)){
		$ignores=array();
	}
	$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path),
			RecursiveIteratorIterator::SELF_FIRST);
	
	foreach($iterator as $file) {
		$refused = false;
		if($file->isDir()) {	
				
			if(strpos($file->getRealpath(), '.git') === false){

				foreach($ignores as $ignore){
					if(strpos($file->getRealpath().'/', '/'.$ignore.'/') !== false && strlen($ignore) > 0){
						$refused = true;
					}
				}				
					
				if($refused == false && $file->getFilename() != '.' && $file->getFilename() != '..'){
					$paths[$file->getRealpath()] =  $file->getRealpath();			
				}
			}
		}
	}
	return $paths;
}

function extractLibrariesFromFile($filename)
{
	$libraries = array('active' => array(), 'inactive' => array());
	$filelines = file ( $filename);
	
	foreach($filelines as $fileline){
		$cleanline = trim($fileline);
		
		if(strpos ($cleanline  , '#include' ) !== false){
			$lib = extractLibrarieFromTextline($cleanline);
			
			if($lib['active']==true){
				$libraries['active'][] = $lib;
			}else{
				$libraries['inactive'][] = $lib;
			}
		}
	}
	
	return $libraries;
}

function extractLibrarieFromTextline($textline)
{
	$lib = array();
	$active =  !(strpos ($textline  , '//' )===0) ;
	$textline = trim($textline, '/');
	$data = preg_split ( '/<(.*?)>/' ,  $textline, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	
	$lib['active'] = $active;
	$lib['name'] = trim(str_replace('.h', '', $data[1] ));
	$lib['header'] = $data[1];
	
	return $lib;
}