const {spawn,spawnSync}=require('node:child_process');
const fs=require('node:fs');
const os=require('node:os');
const path=require('node:path');
const crypto=require('node:crypto');
const {chromium}=require('@playwright/test');
const verify=require('./prototipos.cjs');
(async()=>{
    const root=path.resolve(__dirname,'../..');
    const temp=fs.mkdtempSync(path.join(os.tmpdir(),'mpb-ui-'));
    fs.mkdirSync(path.join(temp,'views'));
    const database=path.join(temp,'database.sqlite');fs.writeFileSync(database,'');
    const password=crypto.randomBytes(20).toString('hex');
    const port=process.env.MPB_UI_PORT || '8097';
    const baseURL='http://127.0.0.1:'+port;
    const env={...process.env,APP_ENV:'testing',APP_KEY:'base64:'+crypto.randomBytes(32).toString('base64'),APP_DEBUG:'true',APP_URL:baseURL,DB_CONNECTION:'sqlite',DB_DATABASE:database,DB_URL:'',SESSION_DRIVER:'file',CACHE_STORE:'array',QUEUE_CONNECTION:'sync',MAIL_MAILER:'log',BCRYPT_ROUNDS:'4',MPB_UI_PASSWORD:password,VIEW_COMPILED_PATH:path.join(temp,'views'),APP_CONFIG_CACHE:path.join(temp,'config.php')};
    let server,browser;
    try{
        for(const args of [['artisan','migrate','--force','--no-interaction'],['artisan','db:seed','--class=BrowserTestSeeder','--force','--no-interaction']]){
            const result=spawnSync('php',['-d','extension=pdo_sqlite',...args],{cwd:root,env,encoding:'utf8'});
            if(result.status!==0)throw new Error('Preparação do banco de teste falhou: '+result.stderr+' '+result.stdout);
        }
        server=spawn('php',['-d','extension=pdo_sqlite','-S','127.0.0.1:'+port,'-t',path.join(root,'public'),path.join(root,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:path.join(root,'public'),env,stdio:['ignore','ignore','pipe']});
        let serverErrors='';server.stderr.on('data',chunk=>serverErrors+=chunk.toString());
        let ready=false;
        for(let attempt=0;attempt<60;attempt++){
            if(server.exitCode!==null)throw new Error('Servidor de teste encerrou: '+serverErrors);
            try{if((await fetch(baseURL+'/up')).ok){ready=true;break;}}catch{}
            await new Promise(resolve=>setTimeout(resolve,250));
        }
        if(!ready)throw new Error('Servidor de testes indisponível: '+serverErrors.slice(-2500));
        const options={headless:true};
        if(process.env.MPB_UI_CHANNEL)options.channel=process.env.MPB_UI_CHANNEL;
        browser=await chromium.launch(options);
        await verify(browser,baseURL,password,root);
    }finally{
        await browser?.close();
        if(server){server.kill();await new Promise(resolve=>server.once('exit',resolve));}
        if(path.dirname(temp)===os.tmpdir() && path.basename(temp).startsWith('mpb-ui-'))fs.rmSync(temp,{recursive:true,force:true});
    }
})().catch(error=>{console.error(error.message);process.exitCode=1;});
