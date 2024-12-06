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
    public static $config;
    /**
     * Register services.
     */
    public function register(): void
    {
        if(Config('Amer.TCPDF.package_path')){
            self::$pachaPath=cleanDir(Config('Amer.tcpdf.package_path'));
        }else{
            self::$pachaPath=cleanDir(__DIR__);
        }
        $this->loadhelper();
        //
        //$this->commands($this->commands);
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadConfigs();
        self::$config=Config('Amer.TCPDF');
        if(Config('Amer.TCPDF.package_path')){
            self::$pachaPath=cleanDir(Config('Amer.TCPDF.package_path'));
        }else{
            self::$pachaPath=cleanDir(__DIR__);
        }
        $this->loadViewsFrom(cleanDir([self::$pachaPath,'view']), 'TCPDF');
        $this->loadTranslationsFrom(cleanDir([self::$pachaPath,"lang"]), 'PDFLANG');
        $this->loadroutes($this->app->router);
        $this->loadDirconfig(['cachePath'=>'temp','fontsPath'=>'fonts','imagesPath'=>'imagesPath']);
        //dd(config('Amer.TCPDF'));
        $this->loadOtherConfigs();
    }
    public function loadConfigs(){
        foreach(getallfiles(__DIR__.'/config') as $file){
            if(!Str::contains($file, 'config'.DIRECTORY_SEPARATOR."Amer".DIRECTORY_SEPARATOR)){
                $name=Str::afterLast(Str::remove('.php',$file),'config'.DIRECTORY_SEPARATOR);
            }else{
                $name='Amer.'.ucfirst(Str::afterLast(Str::remove('.php',$file),'config'.DIRECTORY_SEPARATOR."Amer".DIRECTORY_SEPARATOR));
            }

            $this->mergeConfigFrom(
                $file,$name
            );
        }
    }
    public function loadroutes(Router $router)
    {
        $routepath=getallfiles(cleanDir([self::$pachaPath,'route']));
        foreach($routepath as $path){
            if(!\Str::contains($path, 'api.php')){
                $this->loadRoutesFrom($path);
            }else{
                Route::group($this->apirouteConfiguration(), function () use($path){
                    $this->loadRoutesFrom($path);
                });
            }
        }
    }
    protected function apirouteConfiguration()
    {
        return [
            'prefix' =>'api/'.config('Amer.Amer.api_version')??'v1',
            'middleware' => 'client',
            'name'=>(config('Amer.TCPDF.routeName_prefix') ?? 'amer').'Api',
            'namespace'  =>\Str::finish(config('Amer.TCPDF.Controllers','\\Amerhendy\TCPDF\App\Http\Controllers\\'),'\\'),
        ];
    }
    function loadDirconfig($rr){
        $pb=self::$pachaPath;
        foreach ($rr as $key => $value) {
            $folderName=config("Amer.TCPDF.".$key);
            if($folderName == null || $folderName == false){
                $dir=$this->getDefaultFolder($value);
                App('config')->set(['Amer.TCPDF.'.$key=>$dir]);
            }else{
                if(is_dir($folderName)){
                    App('config')->set(['Amer.TCPDF.'.$key=>realpath($folderName)]);
                }else{
                    $dir=self::$pachaPath.DIRECTORY_SEPARATOR.$folderName;

                    if(cleanDir($dir)){$dir=cleanDir($dir);}else{;mkdir($dir,0777,true);$dir=cleanDir($dir);}
                    App('config')->set(['Amer.TCPDF.'.$key=>$dir]);
                }

            }
        }
    }
    function getDefaultFolder($type){
        if($type == 'temp'){
            return ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
        }elseif ($type == 'fonts') {
            return self::$pachaPath.'fonts/';
        }
    }
    function loadOtherConfigs(){
        $this->setK_PATH_URL();
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
        $this->setLogo();

        if(config('Amer.TCPDF.PageSize') == null || config('Amer.TCPDF.PageSize') == ''){App('config')->set(['Amer.TCPDF.PageSize'=>'A4']);}
        if(config('Amer.TCPDF.PageOrientation') == null || config('Amer.TCPDF.PageOrientation') == '' || !in_array(config('Amer.TCPDF.PageOrientation'),['P,L'])){App('config')->set(['Amer.TCPDF.PageOrientation'=>'P']);}
        if(config('Amer.TCPDF.BlankImage') == null || config('Amer.TCPDF.BlankImage') == ''){
            $blankimg=false;
        }else{
            if(!file_exists(config('Amer.TCPDF.BlankImage'))){
                if(!file_exists(config('Amer.TCPDF.imagesPath').config('Amer.TCPDF.BlankImage'))){
                    $blankimg=false;
                }else{
                    $blankimg=config('Amer.TCPDF.imagesPath').DIRECTORY_SEPARATOR.config('Amer.TCPDF.BlankImage');
                }

            }else{$blankimg=config('Amer.TCPDF.imagesPath').DIRECTORY_SEPARATOR.config('Amer.TCPDF.BlankImage');}
        }
        if($blankimg == false){
            $blankimg=public_path('_blank.png');
        }
        App('config')->set(['Amer.TCPDF.BlankImage'=>$blankimg]);
        $margins=[
            'PDFCreator'=>'Amer Hendy','PDFAuthor'=>'Amer Hendy','PDFHeaderTitle'=>'AmerPdf','PDFHeaderString'=>'By Amer Hendy','PDFUnit'=>'mm',
            'PdfMargin.MarginHeader'=>5,'PdfMargin.MarginTop'=>27,'PdfMargin.MarginFooter'=>10,'PdfMargin.MarginBottom'=>25,'PdfMargin.MarginLeft'=>10,'PdfMargin.MarginRight'=>10,
            'Font.Main.name'=>'helvetica','Font.Main.Size'=>10,'Font.Date.name'=>'courier','Font.Date.Size'=>8,'Font.MONOSPACED'=>'courier','ImageScaleRatio'=>1.25,
            'HeadMagnification'=>1.1,'CellHeightRation'=>1.25,'TitleMagnifation'=>1.3,'SmallRetio'=>2/3,'ThaiTopchars'=>true,'CallsInHTML'=>false,'TrowExeptionError'=>false,
            'timezone'=>config('Amer.Amer.timeZone') ?? date_default_timezone_get()
        ];

        foreach ($margins as $key => $value) {
            if(config('Amer.TCPDF.'.$key) == null || config('Amer.TCPDF.').$key == ''){App('config')->set(['Amer.TCPDF.'.$key=>$value]);}
        }
    }
    public function setLogo(){
        //dd(config('Amer.Amer.public_path'));
        if(config('Amer.TCPDF.pdfHeaderLogo.Src') == null || config('Amer.TCPDF.pdfHeaderLogo.Src') == ''){
            $configLogo=false;
        }else{
            $configLogo=config('Amer.TCPDF.pdfHeaderLogo.Src');
        }
        if($configLogo === false){
            $public=cleanDir(config('Amer.Amer.public_path'));
            $file=realpath(cleanDir([$public]).'/'.config('Amer.Amer.co_logoGif'));
            if(File::exists($file)){
                $configLogo=$file;
            }else{
                $configLogo=false;
            }
        }

        App('config')->set(['Amer.TCPDF.pdfHeaderLogo.Src'=>$configLogo]);
        if($configLogo !== false){
            if(config('Amer.TCPDF.pdfHeaderLogo.Width') == '' || config('Amer.TCPDF.pdfHeaderLogo.Width') == null){
                App('config')->set(['Amer.TCPDF.pdfHeaderLogo.Width'=>'30']);
            }
        }

    }
    public function loadhelper(){
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('TCPDF',Helpers\TCPDF::class);
        $loader->alias('TCPDFBarcode',Helpers\TCPDFBarcode::class);
        $loader->alias('TCPDF2DBarcode',Helpers\TCPDF2DBarcode::class);
        $loader->alias('TCPDF_PARSER',Helpers\TCPDF_PARSER::class);
    }
    public function setK_PATH_URL(){

        if (isset($_SERVER['HTTP_HOST']) AND (!empty($_SERVER['HTTP_HOST']))) {
            if(isset($_SERVER['HTTPS']) AND (!empty($_SERVER['HTTPS'])) AND (strtolower($_SERVER['HTTPS']) != 'off')) {
                $k_path_url = 'https://';
            } else {
                $k_path_url = 'http://';
            }
            $k_path_url .= $_SERVER['HTTP_HOST'];
            $k_path_url .= str_replace( '\\', '/', substr(config('Amer.TCPDF.packagePath'), (strlen($_SERVER['DOCUMENT_ROOT']) - 1)));
            App('config')->set(['Amer.TCPDF.PATH_URL'=>$k_path_url]);
        //dd(config('Amer.TCPDF'));
        }
    }
    public function provides()
    {
        return ['TCPDF','TCPDF_PARSER'];
    }
}
