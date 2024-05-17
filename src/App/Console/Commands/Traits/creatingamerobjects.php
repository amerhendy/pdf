<?php
namespace Amerhendy\Pdf\App\Console\Commands\Traits;
use Illuminate\Support\Facades\App;
use Artisan;
use Illuminate\Console\Command;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use AmerHelper;
trait creatingamerobjects
{
    use PrettyCommandOutput;    
    public function modeltypes($wanted='all'){
        $modeltypes=[
            1=>['Name'=>'Admin','Dir'=>app_path('Models/Admin'),'nameSpace'=>'App/Models/Admin','route'=>'/routes/Amer/admin.php'], 
            2=>['Name'=>'Employer','Dir'=>app_path('Models/Employer'),'nameSpace'=>'App/Models/Employer','route'=>'/routes/Amer/employer.php'] ,
            3=>['Name'=>'Employment','Dir'=>app_path('Models/Employment'),'nameSpace'=>'App/Models/Employment','route'=>'/routes/Amer/employment.php'],
            4=>['Name'=>'Public','Dir'=>app_path('Models'),'nameSpace'=>'App/Models','route'=>'/routes/Amer/public.php'],
            0=>['Name'=>'Root','Dir'=>base_path('vendor/Amerhendy/Employment/src/App/Models'),'nameSpace'=>'Amerhendy/Employment/App/Models','route'=>'vendor/AmerHendy/Employment/src/route/EmploymentRoute.php'],
        ];
        if($wanted == 'Name'){
            return Arr::map($modeltypes,function($v,$k){return $v['Name'];});
        }
        return $modeltypes;
    }
    function createclassname($classname,$wanted){
        $classname=trim($classname);
        if(($classname == '') || (is_numeric($classname)) || (is_null($classname)) || (strlen($classname)<3)){$this->errorBlock("Please Insert Right Name");exit();}
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬-]/', $classname))
        {
            $this->errorBlock('The class has symbol!');exit();
        }
        if(Str::isAscii($classname) == false){$this->errorBlock('The class has symbol!');exit();}

        if(Str::contains($classname,'/')){$sperator='/';}elseif(Str::contains($classname,'\\')){$sperator='\\';}
        if(isset($sperator)){
            //set name Space
            $namespace=Str::ucfirst(Str::beforeLast($classname,$sperator));
            if($sperator == '/'){$namespace=Str::replace('/','\\',$namespace);}
            //$namespace=Str::finish($namespace,'\\');
            $oldnamespace=Str::replace('//','/',$this->modeltypes[$this->classtypeid]['nameSpace']);
            $a=Arr::map(explode('/',$oldnamespace),function($v,$k){return Str::ucfirst($v);});
            $b=Arr::map(explode('\\',$namespace),function($v,$k){return Str::ucfirst($v);});
            $map=Arr::collapse([$a,$b]);
            $unique = array_unique($map);
            $newnamesp=implode('/',$unique);
            $this->classinfo['Model']['nameSpace']=$newnamesp;
            $this->classinfo['controller']['nameSpace']=Str::replace('Models','Http\Controllers',$newnamesp);
            $this->classinfo['request']['nameSpace']=Str::replace('Models','Http\Requests',$newnamesp);
            $classname=Str::afterLast($classname,$sperator);
        }else{
            //setnamespace
            $oldnamespace=Str::replace('/','\\',$this->modeltypes[$this->classtypeid]['nameSpace']);
            $this->classinfo['Model']['nameSpace']=$oldnamespace;
            $this->classinfo['controller']['nameSpace']=Str::replace('Models','Http\Controllers',$oldnamespace);
            $this->classinfo['request']['nameSpace']=Str::replace('Models','Http\Requests',$oldnamespace);
        }
        if(Str::contains($classname,'_')){
            $classname=explode("_",$classname);
            foreach($classname as $a=>$b){
                $classname[$a]=str::headline(Str::squish($b));
            }
            $classname=implode("_",$classname);
            $classname = Str::pluralStudly($classname, 2);
        }else{
            $classname = Str::squish($classname);
            $classname = Str::headline($classname);
            $classname=Str::replace(' ','',$classname);
        }
        $classname = Str::pluralStudly($classname, 2);
        $this->classname=$classname;
        $this->classinfo['Model']['className']=$classname;
        $this->classinfo['controller']['className']=$classname."AmerController";
        $this->classinfo['request']['className']=$classname."Request";
        $this->classinfo['Model']['call']=$this->classinfo['Model']['nameSpace'].'/'.$this->classinfo['Model']['className'];
        $this->classinfo['controller']['call']=$this->classinfo['controller']['nameSpace'].'/'.$this->classinfo['controller']['className'];
        $this->classinfo['request']['call']=$this->classinfo['request']['nameSpace'].'/'.$this->classinfo['request']['className'];
        $namespace=Str::replace('/','\\',$this->classinfo['Model']['nameSpace']);
        $src=$this->modeltypes[$this->classtypeid]['Dir'];
        $src=Str::replace('/','\\',$src);
        if($this->classtypeid == 0){
            $namespace=Str::after($namespace,'Amerhendy\Employment');
            if(Str::contains($namespace,'App')){
                $namespace=Str::replace('App\\','',$namespace);
                if(Str::contains($namespace,'Models')){$namespace=Str::replace('Models\\','',$namespace);}
            }
            
            //dd($namespace);
            $newnamesp=$src.$namespace;
        }else{
            $newsrc=Str::after($src,base_path());
            $newnamesp=$namespace;
            if(Str::contains($newsrc,'\\')){$newsrc=explode('\\',$newsrc);}else{$newsrc=explode('/',$newsrc);}$newsrc=Arr::map($newsrc,function($v,$k){return Str::ucfirst($v);});
            if(Str::contains($newnamesp,'\\')){$newnamesp=explode('\\',$newnamesp);}else{$newnamesp=explode('/',$newnamesp);}$newnamesp=Arr::map($newnamesp,function($v,$k){return Str::ucfirst($v);});
            $newn=array_unique(Arr::collapse([$newsrc,$newnamesp]));
            $newnamesp=base_path(implode('\\',$newn));
        }
        $newnamesp=Str::replace('Models\Models','Models\\',$newnamesp);
        $controllerSrc=Str::replace('Models','Http\Controllers',$newnamesp);
        $RequestSrc=Str::replace('Models','Http\Requests',$newnamesp);
        $this->classinfo['Model']['Src']=Str::replace('\\','/',$newnamesp);
        $this->classinfo['controller']['Src']=Str::replace('\\','/',$controllerSrc);
        $this->classinfo['request']['Src']=Str::replace('\\','/',$RequestSrc);
        $this->classinfo['lang']['file']=$classname;
        $this->classinfo['route']=$this->createrouteifo();
        $this->classinfo['menu']=$this->createmenucode();
        ////////////////creating Table
        $this->classinfo['migration']['file']=$classname.'.php';
        $this->classinfo['migration']['table']=$classname;
    }
    function createrouteifo(){
        $classtype=$this->classtypeid;
        $routepath=$this->modeltypes[$classtype]["route"];
        $classname=$this->classname;
        if(!in_array($classtype,[3,4])){$method='Amer';}else{$method='get';}
        $controller=$classname."AmerController";
        $namespace=$this->classinfo['Model']['nameSpace'];
        $pathnamespace=$this->modeltypes[$classtype]["Dir"];
        if(Str::contains($namespace,$pathnamespace)){
            $plusnamespace=Str::after($namespace,$pathnamespace);
            $plusnamespace=Str::replace('/','\\',$plusnamespace);
            $plusnamespace=Str::finish($plusnamespace,'\\');
            if(Str::startswith($plusnamespace,'\\')){
                $plusnamespace=Str::after($plusnamespace,'\\');
            }
            $controller=$plusnamespace.$controller;
        }
        if($classtype == 0){
            $wnamespace=Str::replace('/','\\',$namespace);$wnamespace=Str::replace('Models','Http\Controllers',$wnamespace);
            $taer=Str::after($wnamespace,config('amer.Controllers'))."\\".$controller;
            $taer=Str::after($taer,'\\');
            $code="Route::$method('$classname','$taer');";
            $end="});";
        }elseif($classtype == 1){
            $wnamespace=Str::replace('/','\\',$namespace);$wnamespace=Str::replace('Models','Http\Controllers',$wnamespace);
            $taer=Str::after($wnamespace.'\\'.$controller,"App\Http\Controllers\\Admin\\");
            $code="Route::$method('$classname','$taer');";
                    $end="});";
        }elseif($classtype == 2){
            $wnamespace=Str::replace('/','\\',$namespace);$wnamespace=Str::replace('Models','Http\Controllers',$wnamespace);
            $taer=Str::after($wnamespace.'\\'.$controller,"App\Http\Controllers\\Employer\\");
            $code="Route::$method('$classname','$taer');";
                    $end="});";
        }else{
            $wnamespace=Str::replace('/','\\',$namespace);$wnamespace=Str::replace('Models','Http\Controllers',$wnamespace);
            $taer=Str::finish($wnamespace,'\\').$controller;
            $code="Route::$method('$classname','$taer');";
            $end='';
        }
        return(['Src'=>$routepath,'end'=>$end,'code'=>$code,]);  

        //['start'=>'','end'=>'','routePath'=>$routePath,'code'=>$code,],
    }
    function createmenucode(){
        $classtype=$this->classtypeid;
        $classname=$this->classname;
        $menucode='<!-- {{Amerurl(\''.$classname.'\')}} --><li class="nav-item"><a href="{{Amerurl(\''.$classname.'\')}}" class="white-text nav-link"><span class="fab fa-servicestack"></span>{{trans(\'AMER::'.$classname.'.'.$classname.'\')}}</a></li>';
        if($classtype == 1){$dp='admin';}elseif($classtype == 2){$dp='employer';}elseif($classtype == 3){$dp='mainmenu';}elseif($classtype == 4){$dp='mainmenu';}
        if(in_array($classtype,[1,2,3,4])){
            $public=resource_path('views/vendor/Amer/Base/inc/menu/'.$dp.'.blade.php');
        }elseif($classtype == 0){
            $public=resource_path('views/vendor/Amer/Base/inc/menu/admin.blade.php');
        }
        return ['Code'=>$menucode,'Src'=>$public];
    }
    function check_model_exists(){
        $src=$this->classinfo['Model']['Src'];
        $modelname=$this->classinfo['Model']['className'];
        return $this->checkclassExists($src,$modelname);
    }
    function check_controller_exists(){
        $src=$this->classinfo['controller']['Src'];
        $modelname=$this->classinfo['controller']['className'];
        return $this->checkclassExists($src,$modelname);
    }
    function check_request_exists(){
        $src=$this->classinfo['request']['Src'];
        $modelname=$this->classinfo['request']['className'];
        return $this->checkclassExists($src,$modelname);
    }
    function check_lang_exists(){
        $src=lang_path().'\\'.app::currentLocale();
            if(File::exists($src)){
                $filename=$this->classinfo['lang']['file'];
                $wantedfile=$src.'\\'.$filename.'.php';
                if(File::exists($wantedfile)){
                    return [
                        'error'=>"Exists",
                        'Src'=>$wantedfile,
                        'complete'=>1
                    ];
                    $this->line('');
                    $this->line('* you had an old language file name called '.$filename);
                    $this->line('<fg=white;bg=red> '.$wantedfile.' </>');
                    $this->line('* if you want to complete working with the same file press yes');
                    $this->line('* if you does not want to complete working with the same file press no and restart to write another model name');

                    $langconf=$this->confirm('Do you want to complete?',false);
                    if($langconf == false){
                        exit();
                    }else{
                        $this->classinfo['lang']['file']=$filename;
                        $this->classinfo['lang']['Src']=$wantedfile;
                        $this->classinfo['creatinglanguage']=false;
                        return;
                    }
                }
                $this->classinfo['creatinglanguage']=true;
                $this->classinfo['lang']['file']=$filename;
                $this->classinfo['lang']['Src']=$wantedfile;
                return;
            }else{
                return [
                    'error'=>'publish',
                    'Code'=>"vendor:publish --tag=Amer:lang",
                    'complete'=>0
                ];
            }
    }
    function check_migration_exists(){
        $src=database_path('migrations');
            if(File::exists($src)){
                $filename=$this->classinfo['migration']['file'];
                $files=File::exists($src.'\\'.$filename);
                if($files == true){
                    return [
                        'error'=>'Exists',
                        'Src'=>Str::replace('/','\\',$src.'\\'.$filename),
                        'complete'=>1
                    ];
                }else{
                    return null;
                }
            }
    }
    function checkclassExists($src,$classname){
        $direxit=false;
        if(File::exists($src)){
            $direxit=true;
        }
        if($direxit == true){
            $amerhenlpers=new AmerHelper();
            $allmodels=$amerhenlpers::getModels($src);
            $allmodels=Arr::where($allmodels,function($v,$k)use($classname){
                return $v["className"] == $classname;
            });
            if(count($allmodels) == 1){
                    return [
                        'error'=>'Exists',
                        'Src'=>Str::replace('/','\\',$src.'\\'.$classname),
                        'complete'=>1
                    ];
            }elseif(count($allmodels) == 0){
                return null;
            }
            else{
                dd(__LINE__,$allmodels);
            }

            if($wanted == 'createrequest'){
                if(count($allmodels) == 1){
                    $this->errorBlock('Sorry this Request added Before, Please Change The Name... Good luck <fg=white;bg=red> '.$src.'\\'.$modelname.' </>');
                    $confirm=$this->confirm('do you want to go to next steps without building Request?');
                    if($confirm == false){
                        exit();
                    }else{
                        $this->classinfo['creatingrequest']=false;
                        return;
                    }
                }
            }
            if($wanted == 'createController'){
                if(count($allmodels) == 1){
                    $this->errorBlock('Sorry this Controller added Before, Please Change The Name... Good luck <fg=white;bg=red> '.$src.'\\'.$modelname.' </>');
                    $confirm=$this->confirm('do you want to go to next steps without building Controller?');
                    if($confirm == false){
                        exit();
                    }else{
                        $this->classinfo['creatingcontroller']=false;
                        return;
                    }
                }
                //dd("SD");
            }
            
            if($wanted == 'createModel'){
                if(count($allmodels) == 1){
                    return [
                        'error'=>'Exists',
                        'Src'=>$src.'\\'.$modelname,
                        'complete'=>1
                    ];
                }
            }
        }else{
        }
    }
    public function insertLink(){
        $src=$this->classinfo["menu"]["Src"];
        $code=$this->classinfo["menu"]["Code"];
        $file_lines = file($src, FILE_IGNORE_NEW_LINES);
        if ($this->getLastLineNumberThatContains($code, $file_lines)) {
            $this->closeProgressBlock('Already existed', 'yellow');
            return;
        }
        $end_line_number = count($file_lines);
        $file_lines[$end_line_number] =$code;
        $new_file_content = implode(PHP_EOL, $file_lines);
        if (! File::put($src, $new_file_content)) {
            $this->errorProgressBlock();
            $this->note('Could not write to file.', 'red');
            return;
        }
    }
    public function getstub($type){
        $path=__DIR__."\\..\..\stubs\\";
        if($type=='Model'){
            $file='model-admin';
        }
        if($type=='controller'){
            $file='controller.model.admin';
        }
        if($type=='request'){
            $file='request';
        }
        if($type=='migration'){
            $file='migration.create';
        }
        if($type == 'lang'){
            $file='lang';
        }
        return File::get($path.$file.'.stub');
    }
    public function creat($type){
        if($type == 'migration'){
            $src=database_path('migrations');
        }elseif($type == 'lang'){
            $src=Str::beforeLast($this->classinfo[$type]['Src'],'\\');
        }else{
            $src=$this->classinfo[$type]['Src'];
        }
            $stub=$this->getstub($type);
            if($type == 'Model'){$stub=$this->createModel($stub);}
            if($type == 'controller'){$stub=$this->createController($stub);}
            if($type == 'request'){$stub=$this->createRequest($stub);}
            if($type == 'migration'){$stub=$this->createmigration($stub);}
            if($type == 'lang'){$stub=$this->createlang($stub);}
            if($type == "migration")
            {
                $destinationclassPath=$src.'\\'.$this->classinfo[$type]['file'];
            }
            elseif($type == 'lang')
            {
                $destinationclassPath=$this->classinfo[$type]['Src'];
            }
            else
            {
                $destinationclassPath=$this->classinfo[$type]['Src'].'/'.$this->classinfo[$type]['className'].'.php';
            }
        if(!File::exists($src)){
            $dirname=File::dirname($src);
            if(!File::exists($dirname)){
                File::makeDirectory($dirname,0755,true);
            }
            if(File:: isWritable($dirname)){
                File::makeDirectory($src);
            }else{
                File::makeDirectory($src);
            }
        }
        if(File::put($destinationclassPath, $stub))
        {$this->infoBlock($type.' created successfully.'.$destinationclassPath);}
        else{
            dd(__LINE__);
        }
    }
    public function createModel($stub){
        $namespace=Str::replace('/','\\',$this->classinfo['Model']['nameSpace']);
        $class=$this->classinfo['Model']['className'];
        $table=$this->classinfo['migration']['table'];
        $stub = Str::replace('{{ namespace }}', $namespace, $stub);
        $stub = Str::replace('{{ class }}', $class, $stub);
        $stub = Str::replace('DummyTable', $table, $stub);
        return $stub;
    }
    public function createRequest($stub){
        $namespace=Str::replace('/','\\',$this->classinfo['request']['nameSpace']);
        $class=$this->classinfo['request']['className'];
        $stub = Str::replace('DummyNamespace', $namespace, $stub);
        $stub = Str::replace('DummyClass', $class, $stub);
        return $stub;
    }
    public function createmigration($stub){
        $table=$this->classinfo['migration']['table'];
        $stub = Str::replace('{{ table }}', $table, $stub);
        return $stub;
    }
    public function createlang($stub){
        $class=$this->classinfo['Model']['className'];
        $headlines=Str::title(Str::headline($class));
        $singular=Str::singular($headlines);
        $plural=Str::plural($headlines);
        $single=Str::words($singular,1,'');
        $stub = Str::replace('{{ className }}', $class, $stub);
        $stub = Str::replace('{{ TransclassName }}', $headlines, $stub);
        $stub = Str::replace('{{ Transsingular }}', $singular, $stub);
        $stub = Str::replace('{{ Transplural }}', $plural, $stub);
        $stub = Str::replace('{{ Transcreate }}', 'Create '.$single, $stub);
        $stub = Str::replace('{{ Transedit }}', 'Edit '.$single, $stub);
        return $stub;
    }
    public function createController($stub){
        $namespace=Str::replace('/','\\',$this->classinfo['controller']['nameSpace']);
        $namespacedModel=Str::start(Str::replace('/','\\',$this->classinfo['Model']['call']),'\\');
        $model=$this->classinfo['Model']['className'];
        $InsertnamespacedRequests=Str::start(Str::replace('/','\\',$this->classinfo['request']['call']),'\\');
        $DummyClassRequest=$this->classinfo['request']['className'];
        $controller=$this->classinfo['controller']['className'];
        $stub = Str::replace('{{ namespace }}', $namespace, $stub);
        $stub = Str::replace('{{ namespacedModel }}', $namespacedModel, $stub);
        $stub = Str::replace('{{ model }}', $model, $stub);
        $stub = Str::replace('{{ class }}', $controller, $stub);
        $stub = Str::replace('{{ rootNamespace }}', 'App\\', $stub);
        $stub = Str::replace('{{ InsertnamespacedRequests }}', $InsertnamespacedRequests, $stub);
        $stub = Str::replace('DummyClassRequest', $DummyClassRequest, $stub);
        return $stub;
    }
    public function insertRoute(){
        $src=Str::replace('/','\\',base_path($this->classinfo["route"]["Src"]));
        $end=$this->classinfo["route"]["end"];
        $code=$this->classinfo["route"]["code"];
        $file_lines = file($src, FILE_IGNORE_NEW_LINES);
        if($this->classtypeid == 0){
            $prefix="'prefix'=>config('amer.route_prefix','amer'),";
            $namespace="'namespace' => config('amer.Controllers'),";
            $middle="'middleware' =>array_merge((array) config('amer.web_middleware'),(array) config('amer.auth.admin_auth.middleware_key')),";
            $routegroupname="'name'=>'admin.',";
        }
        elseif($this->classtypeid == 1){
            $prefix="'prefix'     =>config('amer.route_prefix'),";
            $namespace="'namespace'  =>'App\Http\Controllers\Admin',";
            $middle="'middleware' =>array_merge((array) config('amer.web_middleware'),(array) config('amer.auth.admin_auth.middleware_key')),";    
            $routegroupname="'name'=>'admin.',";
        }
        elseif($this->classtypeid == 2){
            $prefix="'prefix'     =>config('amer.employer_route_prefix'),";
            $namespace="'namespace'  =>'App\Http\Controllers\Employer',";
            $middle="'middleware' =>array_merge((array) config('amer.web_middleware'),(array) config('amer.auth.employer_auth.middleware_key')),";    
            $routegroupname="'name'=>'Employer.',";
        }
        elseif($this->classtypeid == 3){
            $prefix="'prefix'     =>config('amer.route_prefix'),";
            $namespace="'namespace'  =>'App\Http\Controllers\Employment',";
            $middle="'middleware' =>array_merge((array) config('amer.web_middleware'),(array) config('amer.auth.admin_auth.middleware_key')),";
            $routegroupname="'name'=>'Employment.',";
        }else{
            $prefix=$namespace=$middle=$routegroupname="";
        }
        $start=[];
        $start[]="Route::group(";
        $start[]='[';
        $start[]=$prefix;
        $start[]=$namespace;
        $start[]=$middle;
        $start[]=$routegroupname;
        $start[]="],";
        $start[]="function(){";
        if($this->classtypeid == 4){
            $start=[];
        }
        $content=File::lines($src);
        if($start == ''){
            $content=$content->toArray();
            Arr::set($content,count($content)+1,$code);
            $new_file_content = implode(PHP_EOL, $content);
        }else{
            $check=$this->checkmultiplelines($start,$content);
            $chunck=array_chunk($content->toArray(),$check+1);
            $checkchunck=$this->checkmultiplelines([$end],$chunck[1]);
            $vol=array_splice($chunck[1],$checkchunck,0,$code);
            $new_file_content = implode(PHP_EOL, Arr::collapse($chunck));
        }
        if (File::put($src, $new_file_content)) {
            Artisan::call("route:cache");
            $this->infoBlock('Route Hass been Added in <fg=white;bg=red> '.$src.' </>');
            return;
        }else{
            $this->errorBlock('Route Has not been Added in <fg=white;bg=red> '.$src.' </>');
            exit();
            return;
        }
        dd(Arr::collapse($chunck));
        dd($chunck,$vol);
        $checkchunck-1;
        dd($chunck,$checkchunck,$chunck2);
        $matching=Str::contains($content,$start);
        dd($matching);
        if($matching == 'true'){
            $slice = Str::between($content, $start, $end);
            $newdata="".$slice.$code."\r\n";
            $replaced = Str::replace($slice, $newdata, $content);
            File::put($src,$replaced);
            Artisan::call("route:cache");
            $this->infoBlock('Route Hass been Added in <fg=white;bg=red> '.$src.' </>');
        }else{
            $this->errorBlock('Route Has not been Added in <fg=white;bg=red> '.$src.' </>');
        }
    }
    function checkmultiplelines($lines,$file){  
        $text = $lines;
        $wantedlines=[];
        foreach($file as $a=>$line) {
            if(in_array(trim($line),$lines)){
                $wantedlines[]=$a;
            }
            /*if (strpos($text, trim($line)) !== false) {
                print $line;
                echo 'Found';
            }*/

        }
        if(!count($wantedlines)){dd(__LINE__);}
        return Arr::last($wantedlines);
    }
    function checks(){
        $this->progressBlock('Check Models');
        $check['model']=$this->check_model_exists();
        $this->closeProgressBlock();
        $this->progressBlock('Check Controllers');
        $check['controller']=$this->check_controller_exists();
        $this->closeProgressBlock();
        $this->progressBlock('Check Requests');
        $check['request']=$this->check_request_exists();
        $this->closeProgressBlock();
        $this->progressBlock('Check Migration');
        $check['migration']=$this->check_migration_exists();
        $this->closeProgressBlock();
        $this->progressBlock('Check Languages');
        $check['lang']=$this->check_lang_exists();
        $this->closeProgressBlock();
        $this->box('CHecking Results');
        $tableheader=['<bg=#ff0;fg=red>section</>','<bg=#ff0;fg=red>result</>','<bg=#ff0;fg=red>completing</>','<bg=#ff0;fg=red>creating</>'];
        $tablerow=[];
        if($check['model'] == null){
            $tablerow[]=['model','Success','Ok','yes'];
        }else{
            $tablerow[]=['model',$this->fierrors($check['model'],'model'),'maybe','no'];
        }
        if($check['controller'] == null){
            $tablerow[]=['controller','Success','Ok','yes'];
        }else{
            $tablerow[]=['controller',$this->fierrors($check['controller'],'controller'),'maybe','no'];
        }
        if($check['request'] == null){
            $tablerow[]=['request','Success','Ok','yes'];
        }else{
            $tablerow[]=['request',$this->fierrors($check['request'],'request'),'maybe','no'];
        }
        if($check['migration'] == null){
            $tablerow[]=['Migration','Success','Ok','yes'];
        }else{
            $tablerow[]=['Migration',$this->fierrors($check['migration'],'migration'),'maybe','no'];
        }
        if($check['lang'] == null){
            $tablerow[]=['lang','Success','Ok','yes'];
        }else{
            if($check['lang']['error'] == 'publish'){
                $tablerow[]=['lang','please publish languages using this '.$check['lang']['code'],'no','no'];
            }else{
                $tablerow[]=['lang',$this->fierrors($check['lang'],'lang'),'maybe','no'];
            }
        }
        $this->checkresult=$check;
        $this->table($tableheader,$tablerow);
        foreach($check as $a=>$b){
            if(isset($b['complete'])){
                if($b['complete'] == 0){
                    $this->errorBlock($a.' cause you not to complete');
                    $exit[]=[$a];
                }
            }
        }
        if(isset($exit)){exit();}
    }
    function fierrors($array,$type){
        $text='';
        if($array['error']=="Exists"){
            $text.=$type.' added Before, ';
        }
        if(isset($array['Src'])){
            $text.=' <fg=white;bg=red> '.$array['Src'].' </>';
        }
        return $text;
    }
}