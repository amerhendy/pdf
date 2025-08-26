<?php
namespace Amerhendy\Pdf\App\Console\Commands;
use Illuminate\Support\Facades\App;
use Illuminate\support\ServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use AmerHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Amerhendy\Pdf\includes\TCPDF_FONTS;
class InstallFont extends Command{
	use Traits\PrettyCommandOutput;
	protected $progressBar;
	public $sourcePath,$fontPath;
	protected $progress;
	protected $description = 'start to publish public files before work ... lets start';
	protected $name = 'Amer:installfont';
	protected $signature = 'Amer:installfont	{type : Type}	{enc : enc}	{flags : flags} {platid : platid}	{encid : encid}	{addcbbox : addcbbox}	{file : file}
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : force replace data
    ';
	public static $options=[];
	public $choices=[
		'type'=>['autodetect','TrueTypeUnicode','TrueType','Type1','CID0JP','CID0KR','CID0CS','CID0CT'],
		'enc'=>['default model','Symbol','ZapfDingBats'],
		'flags'=>['fixed font','symbol','non-symbol','italic','AutoDetect italic or Fixed non-symbolic','AutoDetect italic or Fixed symbolic'],
		'platid'=>['Windows','Macintosh'],
		'encid'=>['Symbol','Unicode','ShiftJIS','PRC','Big5','Wansung','Johab','Reserved I','Reserved II','Reserved III','UCS-4'],
		'addcbbox'=>['true','false'],
	];
	public function __construct(){
		
		if(!config('Amer.TCPDF.package_path')){
            $mainpath=Str::finish(__DIR__."",'/').'../../../';
        }else{
            $mainpath=Str::finish(config('Amer.TCPDF.package_path'),'/');
        }
		$mainpath=cleanDir($mainpath);
		$this->sourcePath=realpath($mainpath);
		if(config('Amer.TCPDF.fontsPath')){
			$output=config('Amer.TCPDF.fontsPath');
		}else{
			$output=$this->sourcePath.'/fonts/';
		}
		if(!is_dir($output)){
			$output=$this->sourcePath.$output;
		}
		$this->fontPath=cleanDir(realpath($output));
        parent::__construct();
    }
	protected function interact(InputInterface $input, OutputInterface $output)
    {
		if (!is_dir($this->fontPath)) {
			$this->errorBlock("ERROR: Can't find ".$this->fontPath);
			exit();
		}
		if (!is_writable($this->fontPath)) {
			$this->errorBlock("ERROR: Can't write to ".$this->fontPath);
			exit(3);
		}
        $io = new SymfonyStyle($input, $output);
        $this->box('Welcome to Amer Installer');
		$progressBar=new ProgressBar($output, 50);
		$progressBar->start(0);
			$this->askforfileSrc($io,$input,$progressBar);
		$progressBar->advance(1);
			$this->choosetype($io,$input,$progressBar);
		$progressBar->advance(2);
		$this->chooseenc($io,$input,$progressBar);
		$progressBar->advance(3);
		$this->chooseflags($io,$input,$progressBar);
		$progressBar->advance(4);
		$this->chooseplatid($io,$input,$progressBar);
		$progressBar->advance(5);
		$this->chooseencid($io,$input,$progressBar);
		$progressBar->advance(6);
		$this->chooseaddcbbox($io,$input,$progressBar);
		//$progressBar->finish();
		$this->progressBar=$progressBar;
    }
	public function handle()
    {
		$this->progressBar->advance(7);
		self::$options["outpath"]=$this->fontPath;
		self::$options["link"]=false;
		$options=self::$options;
		$fontfile = $options['file'];
		$errors=false;
		$fontname = TCPDF_FONTS::addTTFfont($fontfile, $options['type'], $options['enc'], $options['flags'], $options['outpath'], $options['platid'], $options['encid'], $options['addcbbox'], $options['link']);
		if ($fontname === false) {
			$errors = true;
			$result="ERROR: can't add ".$fontfile;
		} else {
			$result="+++ OK   : ".$fontfile.' added as '.$fontname;
		}
		if ($errors) {
			$this->errorbox('Error',$result."\n Process completed with ERRORS!",'red');
			exit(4);
		}
		$this->errorBlock($result."\n Process successfully completed!");
		exit(0);
	}
	protected function choosetype($io,$input,$progressBar){
		$io->newLine();
		$io->title('Please Select type');
		$input->setArgument(
			'type',
			$io->choice('Please Select Font Type',$this->choices['type'],0)
		);
		if($this->input->getArgument('type')){
			$val = array_search($this->input->getArgument('type'), $this->choices['type']);
			self::$options['type']=$val;
		}
		
	}
	protected function chooseenc($io,$input,$progressBar){
		$io->newLine();
		$io->title('Please Select Name of the encoding table');
		$input->setArgument(
			'enc',
			$io->choice('Please Select Name of the encoding table',$this->choices['enc'],0)
		);
		if($this->input->getArgument('enc')){
			$val = array_search($this->input->getArgument('enc'), $this->choices['enc']);
			self::$options['enc']=$val;
		}
		
	}
	protected function chooseflags($io,$input,$progressBar){
		$io->newLine();
		$io->title('Please Select Font Descriptor Flags');
		$input->setArgument(
			'flags',
			$io->choice('Please Select Font Descriptor Flags',$this->choices['flags'],1)
		);
		if($this->input->getArgument('flags')){
			$flagsCodes=['+1'=>'fixed font','+4'=>'symbol','+32'=>'non-symbol','+64'=>'italic','32'=>'AutoDetect italic or Fixed non-symbolic','4'=>'AutoDetect italic or Fixed symbolic'];
			$val = array_search($this->input->getArgument('flags'), $flagsCodes);
			self::$options['flags']=$val;
		}
		
	}
	protected function chooseplatid($io,$input,$progressBar){
		$io->newLine();
		$io->title('Please Select Platform');
		$input->setArgument(
			'platid',
			$io->choice('Please Select Platform',$this->choices['platid'],0)
		);
		if($this->input->getArgument('platid')){
			$platidCodes=['3'=>'Windows','1'=>'Macintosh'];
			$val = array_search($this->input->getArgument('platid'), $platidCodes);
			self::$options['platid']=min(max(1, intval($val)), 3);
		}
		
	}
	protected function chooseencid($io,$input,$progressBar){
		$io->newLine();
		if(self::$options['platid'] == 3){
			$encidarr=$this->choices['encid'];
			$dedf=1;
		}else{
			$dedf=0;
			$encidarr=['mac'];
		}
		$io->title('Please Select Platform');
		$input->setArgument(
			'encid',
			$io->choice('Please Select Platform',$encidarr,$dedf)
		);
		if($this->input->getArgument('encid')){
			$val = array_search($this->input->getArgument('encid'), $encidarr);
			self::$options['encid']=min(max(0, intval($val)), 10);
		}
		
	}
	protected function chooseaddcbbox($io,$input,$progressBar){
		$io->newLine();
		$io->title('Please Select bounding box');
		$input->setArgument(
			'addcbbox',
			$io->choice('Includes the character bounding box information on the php font file.',['true','false'],1)
		);
		if($this->input->getArgument('addcbbox')){
			self::$options['addcbbox']=boolval($this->input->getArgument('addcbbox'));
		}
		
	}
	protected function askforfileSrc($io,$input,$progressBar){
		$io->newLine();
		$io->title('Set Font Path');
		$input->setArgument(
			'file',
			$io->ask('type your file path in '.$this->fontPath,'Afsaneh Font.ttf')
		);
		if($this->input->getArgument('file') == ''){
			$this->errorBlock('File not Exists');
			exit();
		}
		$filename=$this->fontPath.'/';
		$filename.=$this->input->getArgument('file');
		if (!file_exists($filename)) {
			$this->errorBlock('File not Exists');
			exit();
		}
		self::$options['file']=$filename;
		self::$options['filename']=$this->input->getArgument('file');
	}
	public function box($header, $color = 'green')
    {
        $line = str_repeat('─', strlen($header));

        $this->newLine();
        $this->line("<fg=$color>┌───{$line}───┐</>");
        $this->line("<fg=$color>│   $header   │</>");
        $this->line("<fg=$color>└───{$line}───┘</>");
    }
	public function errorBlock(string $text)
    {
        $this->infoBlock($text, 'ERROR', 'red');
    }
	public function infoBlock(string $text, string $title = 'info', string $background = 'blue', string $foreground = 'white')
    {
        $this->newLine();
        // low verbose level (-v) will display a note instead of info block
        if ($this->output->isVerbose()) {
            if ($title !== 'info') {
                $text = "$text <fg=gray>[<fg=$background>$title</>]</>";
            }

            return $this->line("  $text");
        }
        $this->line(sprintf("  <fg=$foreground;bg=$background> %s </> $text", strtoupper($title)));
        $this->newLine();
    }
	public function errorbox($header,$body, $color = 'green')
    {
        $line = str_repeat('─', strlen($body));
        $this->newLine();
        $this->line("<fg=$color>┌───{$line}───┐</>");
        $this->line("<fg=red>$header</>");
        $this->line("<fg=$color>│   $body   │</>");
        $this->line("<fg=$color>└───{$line}───┘</>");
    }
}
if (php_sapi_name() != 'cli') {
  echo 'You need to run this command from console.';
  exit(1);
}