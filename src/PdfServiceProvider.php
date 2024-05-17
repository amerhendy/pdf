<?php
namespace Amerhendy\Pdf;
//composer update
//composer dump-autoload
//php artisan vendor:publish
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;  
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
class PdfServiceProvider extends ServiceProvider
{
    public $startcomm="Amer";
    protected $defer = false;
    public static $pachaPath="Amerhendy\Pdf\\";
    protected $commands = [
        App\Console\Commands\InstallFont::class,
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        if(!config('Amer.tcpdf.packagePath')){
            self::$pachaPath=Str::finish(__DIR__,DIRECTORY_SEPARATOR);
        }else{
            if(DIRECTORY_SEPARATOR == '\\'){
                $st=Str::replace('\\',DIRECTORY_SEPARATOR,config('Amer.tcpdf.packagePath'));
            }else{
                $st=Str::replace('/',DIRECTORY_SEPARATOR,config('Amer.tcpdf.packagePath'));
            }
            self::$pachaPath=Str::finish($st,DIRECTORY_SEPARATOR);
        }
        $this->loadhelper();
        $this->commands($this->commands);
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadConfigs();
        $this->loadviewfiles();
        $this->publishFiles();
        $this->loadroutes($this->app->router);
    }
    function loadviewfiles() {
        $basefiles="view";
        if (file_exists($basefiles)) {
            $this->loadViewsFrom($basefiles, 'Amer');
        }
        $this->loadViewsFrom(self::$pachaPath.'resources/views/Amer', 'Amer');
    }
    public function loadConfigs(){
        foreach(getallfiles(__DIR__.'/config') as $file){
            $this->mergeConfigFrom($file,Str::replace('/','.',Str::afterLast(Str::remove('.php',$file),'config/Amer /')));
        }
    }
    /**
     * loadroutes
     *
     * @param Router $router
     * 
     * @return [type]
     */
    public function loadroutes(Router $router)
    {
        
        $routepath=getallfiles(self::$pachaPath.'route/');
        foreach($routepath as $path){
            $this->loadRoutesFrom($path);
        }
    }
    function publishFiles()  {
        $this->app->bind('path.public',function(){
           return config('Amer.amer.public_path'); 
        });
        $pb=self::$pachaPath;
        $config_files = [$pb.'/config' => config_path()];
        $this->publishes($config_files, $this->startcomm.':config');
    }
    function getDefaultFolder($type){
        if($type == 'temp'){
            return ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
        }elseif ($type == 'fonts') {
            return self::$pachaPath.'fonts/';
        }
        
    }
    function lodaDirconfig($string,$type){
        $pb=self::$pachaPath;
        $tmpconfig=config("Amer.tcpdf.".$string);
        $folderName=config("Amer.tcpdf.".$string);
        if($folderName == null || $folderName == false){
            $folderDir=$this->getDefaultFolder($type);
        }else{
            if(DIRECTORY_SEPARATOR == '/'){$folderName=Str::replace('\\',DIRECTORY_SEPARATOR,$folderName);}else{$folderName=Str::replace('/',DIRECTORY_SEPARATOR,$folderName);}
            $folderDir=$pb.$folderName;
            if(!File::exists($folderDir)){
                File::makeDirectory($folderDir,0755,true,true);
            }
        }
        App('config')->set(['Amer.tcpdf.'.$string=>$folderDir]);
    }
    function loadOtherConfigs(){
        if ((!isset($_SERVER['DOCUMENT_ROOT'])) OR (empty($_SERVER['DOCUMENT_ROOT']))) {
            if(isset($_SERVER['SCRIPT_FILENAME'])) {
                $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
            } elseif(isset($_SERVER['PATH_TRANSLATED'])) {
                $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
            } else {
                // define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
                $_SERVER['DOCUMENT_ROOT'] = '/';
            }
        }
        $_SERVER['DOCUMENT_ROOT'] = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']);
        if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/') {
            $_SERVER['DOCUMENT_ROOT'] .= '/';
        }
        //dd(config('Amer.tcpdf.pdfHeaderLogo.Src'));
        if(config('Amer.tcpdf.pdfHeaderLogo.Src') == null || config('Amer.tcpdf.pdfHeaderLogo.Src') == ''){
            $Amerlogo=realpath(config('Amer.amer.public_path').config('Amer.amer.co_logoGif'));
            if(File::exists($Amerlogo)){
                $tcpdf_header_logo=  $Amerlogo;
            }else{
                $tcpdf_header_logo='';
            }
        }else{
            if(File::exists(config('Amer.tcpdf.pdfHeaderLogo.Src'))){$tcpdf_header_logo=  config('Amer.tcpdf.pdfHeaderLogo.Src');}else{
                $tcpdf_header_logo='';
            }
        }
        App('config')->set(['Amer.tcpdf.pdfHeaderLogo.Src'=>$tcpdf_header_logo]);
        if($tcpdf_header_logo !== ''){
            if(config('Amer.tcpdf.pdfHeaderLogo.Width') == '' || config('Amer.tcpdf.pdfHeaderLogo.Width') == null){
                App('config')->set(['Amer.tcpdf.pdfHeaderLogo.Width'=>'30']);
            }
        }
        if(config('Amer.tcpdf.PageSize') == null || config('Amer.tcpdf.PageSize') == ''){App('config')->set(['Amer.tcpdf.PageSize'=>'A4']);}
        if(config('Amer.tcpdf.PageOrientation') == null || config('Amer.tcpdf.PageOrientation') == '' || !in_array(config('Amer.tcpdf.PageOrientation'),['P,L'])){App('config')->set(['Amer.tcpdf.PageOrientation'=>'P']);}
        if(config('Amer.tcpdf.BlankImage') == null || config('Amer.tcpdf.BlankImage') == ''){$blankimg=false;}else{
            if(!file_exists(config('Amer.tcpdf.BlankImage'))){
                if(!file_exists(config('Amer.tcpdf.imagesPath').config('Amer.tcpdf.BlankImage'))){
                    $blankimg=false;
                }else{
                    $blankimg=config('Amer.tcpdf.imagesPath').config('Amer.tcpdf.BlankImage');
                }
                
            }else{$blankimg=config('Amer.tcpdf.BlankImage');}
        }
        if($blankimg == false){
            //createimg
            $blankimg=imagecreate(10,10);
            $white=imagecolorallocate($blankimg,255,255,255);
            $blankimgnaame=config('Amer.tcpdf.BlankImage') ?? '_blank.png';
            imagepng($blankimg,config('Amer.tcpdf.imagesPath').$blankimgnaame);
        }
        App('config')->set(['Amer.tcpdf.BlankImage'=>$blankimg]);
        $margins=[
            'PDFCreator'=>'Amer Hendy','PDFAuthor'=>'Amer Hendy','PDFHeaderTitle'=>'AmerPdf','PDFHeaderString'=>'By Amer Hendy','PDFUnit'=>'mm',
            'PdfMargin.MarginHeader'=>5,'PdfMargin.MarginTop'=>27,'PdfMargin.MarginFooter'=>10,'PdfMargin.MarginBottom'=>25,'PdfMargin.MarginLeft'=>10,'PdfMargin.MarginRight'=>10,
            'Font.Main.name'=>'helvetica','Font.Main.Size'=>10,'Font.Date.name'=>'courier','Font.Date.Size'=>8,'Font.MONOSPACED'=>'courier','ImageScaleRatio'=>1.25,
            'HeadMagnification'=>1.1,'CellHeightRation'=>1.25,'TitleMagnifation'=>1.3,'SmallRetio'=>2/3,'ThaiTopchars'=>true,'CallsInHTML'=>false,'TrowExeptionError'=>false,
            'timezone'=>config('Amer.amer.timeZone') ?? date_default_timezone_get()
        ];
        foreach ($margins as $key => $value) {
            if(config('Amer.tcpdf.'.$key) == null || config('Amer.tcpdf.').$key == ''){App('config')->set(['Amer.tcpdf.'.$key=>$value]);}    
        }
        $pb=self::$pachaPath;
    }
    public function loadhelper(){
        $this->lodaDirconfig('cachePath','temp');
        $this->lodaDirconfig('fontsPath','fonts');
        $this->lodaDirconfig('imagesPath','imagesPath');
        $this->loadOtherConfigs();
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('TCPDF',Helpers\TCPDF::class);
        $loader->alias('TCPDFBarcode',Helpers\TCPDFBarcode::class);
        $loader->alias('TCPDF2DBarcode',Helpers\TCPDF2DBarcode::class);
        $loader->alias('TCPDF_PARSER',Helpers\TCPDF_PARSER::class);
    }
    
    public function provides()
    {
        return ['TCPDF','TCPDF_PARSER'];
    }
}
