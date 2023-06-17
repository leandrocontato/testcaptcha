<?php 
	ob_start();
	
    date_default_timezone_set("America/Araguaina"); // Sem mudança automática do horário de verão
	//date_default_timezone_set("America/Sao_Paulo"); // Mudança automática com a alteração de horário de verão no Brasil
  
	if(2!=session_status()){
		session_set_cookie_params(0);
		session_start();
	}
	
	$DocRoot = $_SERVER['DOCUMENT_ROOT']."/";
	$_server = null;
	if(isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
		$_server = $_SERVER['HTTP_X_FORWARDED_HOST'];
	}else{
		$_server = $_SERVER["HTTP_HOST"];
	}
	
    // Variável que identifica se a requisição ao apache foi com SSL
    $DocUrl = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://').$_server."/";
    if(!(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") && in_array($_server , array( 'www.infoconsig.net.br','infoconsig.net.br') ) ){
		//Somente a produção é direcionado para o HTTPS
		header("Location: https://$_server");
		exit;
	}
	
    $array_servers = array(); 
	// Configuração da produção
	$array_servers[] = "177.70.107.103";
	$array_servers[] = "teste-net";
	$array_servers[] = "www.infoconsig.inf.br";
	$array_servers[] = "infoconsig.inf.br";
	$array_servers[] = "www.infoconsig.net.br";
	$array_servers[] = "infoconsig.net.br";
	
	$array_servers[] = "www.infoconsig.com.br";
	$array_servers[] = "infoconsig.com.br";
	$array_servers[] = "www.torneirafechada.com.br";
	$array_servers[] = "torneirafechada.com.br";
	
	// Configurações para uso interno em virtual host no servidor apache como localhost
	// As configurações de servers abaixo é conforme o arquivo httpd-vhosts.conf
	// Diretório 'infoconsig_dev' - ambiente de desenvolvimento do programador - as muitas opções para esse ambiente é por causa das visões que o sistema usa 
	$array_servers[] = "www.infoconsig.com";
	$array_servers[] = "infoconsig.com";
	
	// Diretório 'infoconsig_alfa' - versão de teste que antecede ao beta
	$array_servers[] = "www.infoconsig-alfa.com";
	
	// Diretório 'infoconsig_beta' - o código fonte deve ser o mesmo do código fonte da versão beta para fins de depuração de erros na versão
	$array_servers[] = "www.infoconsig-beta.com";

	// Diretório 'infoconsig_producao' - o código fonte deve ser o mesmo do código fonte da produção para fins de depuração de erros na produção 
	$array_servers[] = "www.infoconsig-net.com";
    $array_servers[] = "sttorico.infoconsig";
    $array_servers[] = "sttorico.infoconsig:8181";
    $array_servers[] = "sttorico.infoconsig:8182";
    $array_servers[] = "sttorico.infoconsig:8183";
	
	for ($i = 2; $i <= 10; $i++ ){
		$array_servers[] = "www.infoconsig-$i.com";
		$array_servers[] = "www.infoconsig-net-$i.com";		
		$array_servers[] = "www.infoconsig-alfa-$i.com";
		$array_servers[] = "www.infoconsig-beta-$i.com";
	}
	
	$_host = explode(":", $_server)[0]; // Para funcionamento em porta alternativa 
	
	//if( in_array($_server, $array_servers) ) {
	if( in_array($_host, $array_servers) ) {
		$Project = "";
		define( 'PROJECT_FOLDER', $_server );		
	} else {
		$Project = $_SERVER["PHP_SELF"];
		$Project = substr( $Project , strpos( $Project , "/" ) +1 ) ;
		$Project = substr( $Project , 0 , strpos( $Project, '/' ) ) .'/';
		define( 'PROJECT_FOLDER' , str_replace( "/", "",$Project) );
	}
	
	//Para exibição de mensagens diferente. Nos domínios em produção não são exibidos mensagens.
	$oficiais[] = "www.infoconsig.inf.br";
	$oficiais[] = "infoconsig.inf.br";
	$oficiais[] = "www.infoconsig.net.br";
	$oficiais[] = "infoconsig.net.br";
		
	//if( in_array($_server, $oficiais)){
	if( in_array($_host, $oficiais) ) {
	    error_reporting( !E_ALL ); // Nas oficiais erros não exibidos	    
	} else {
	    error_reporting( E_ALL ); // Habilita exibir todo os erros possíveis de sintaxe e fatais no código php
	    //error_reporting(E_ALL ^ (E_NOTICE));
    	//error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED ));
    	//error_reporting(E_ALL ^ (E_DEPRECATED));
	}
		
	if( isset( $_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"] )){
	    // Se for o usuário ADMIN a exibição de erros é ativada
	    if( $_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"] == 1 ){
	        ini_set("display_errors",true);
	    }
	}
	
	if( strpos(PROJECT_FOLDER, 'net.br') > 0 || strpos(PROJECT_FOLDER, 'net') > 0 ){
		//$sysLayout = "old";
		$sysLayout = "padrao";
	}elseif( strpos(PROJECT_FOLDER, 'card') > 0 || strpos(PROJECT_FOLDER, 'beta') > 0 || strpos(PROJECT_FOLDER, 'inf.br') > 0  ){
	    $sysLayout = "padrao";
	} else {
	    $sysLayout = "padrao";
	}
	
	define('SYS_LAYOUT',$sysLayout);
	
	$Core = "Core/";
	$Adm = ""; // Não tem painel para admin do sistema
	$View = "View/";    
	if(!file_exists( $DocRoot.$Project.$View."pwd.inc.php" )){
	    echo "Arquivo de conex&atilde;o com o banco de dados n&atilde;o encontrado!";
		exit;
	} else {
		require_once 'pwd.inc.php';
		
		if(!isset($DataBaseInstanceName)){
		    echo "Database Instance Name não informado!";
		    exit;
		}
		if(!isset($ClassAdoDB)){
		    echo "Classe DB não informada!";
		    exit;
		}
	}

	// Classe Abstrata do Core para Configs.
	$ADODB_LANG = "pt-br";
	require_once($DocRoot.$Project.$Core.'Src/CoreDefault.php');

	/**
	 * Metodo Mágico de carregamento de classes
	 * Não se preocupar com este metodo ele funciona automaticamente
	 * @param string $name
	 */
    function autoload($name) {
        //$f = fopen("aaaaaa.txt","a+"); fwrite( $f , $name . str_repeat("-", 60-strlen($name) ).'>' . CoreDefault::getCoreClass($name) ."\n" ); fclose( $f );
		 // Carrega classe do Core
		if (CoreDefault::isCoreClass($name))
			require_once(CoreDefault::getCoreClass($name));
		else // Carrega classe do Model
		if (CoreDefault::isModelClass($name))
			require_once(CoreDefault::getModelClass($name));
		else // Carrega classe do Controller da Aplicação
		if (CoreDefault::isControllerClass($name))
			require_once(CoreDefault::getControllerClass($name));
		else // Carrega classe do Controller do Cms
		if (CoreDefault::isControllerCms($name))
			require_once(CoreDefault::getControllerCms($name));
	} 
    spl_autoload_register('autoload');
    
	// Caminhos da Aplicação
	CoreDefault::$DocumentRoot			= $DocRoot.$Project;
	CoreDefault::$DocumentUrl			= $DocUrl.$Project;
	CoreDefault::$ViewUrl				= $DocUrl.$Project.$View;
	// Caminhos do Core
	CoreDefault::$CorePath				= $DocRoot.$Project.$Core;
	CoreDefault::$CoreUrl				= $DocUrl.$Project.$Core;
	// Caminhos do CMS ou ADM
	CoreDefault::$CmsRoot				= $DocRoot.$Project;
	CoreDefault::$CmsUrl				= $DocUrl.$Project;
	// Habilita ferramenta de gerar Model
	CoreDefault::$ToolsOn				= true;
	// Caminho do diretorio de idiomas
	CoreDefault::$LanguagePath			= "";
	// Caminho das Classe de Model
	CoreDefault::$ModelPath				= CoreDefault::$DocumentRoot."Model/";
	// Caminho das Classes de Controller
	CoreDefault::$ControllerPath		= CoreDefault::$DocumentRoot."Controller/";
	// Caminho das Classes de Controller do Cms
	CoreDefault::$ControllerCms			= CoreDefault::$DocumentRoot."Controller/";

	// Conexão com Banco de Dados conforme arquivo pwd.inc.php
	CoreDefault::$AdodbHost				= $hostDB;    
	CoreDefault::$AdodbUsername			= $userDB; 
	CoreDefault::$AdodbPassword			= $pwdDB;
	
	CoreDefault::$AdodbType				= "oci8";
	CoreDefault::$AdodbDatabase			= $DataBaseInstanceName;
	//
	// Tipo de Caracter para banco de dados oracle / postgre / interbase
	// Para Oracle é necessário informar o charset
	// CoreDefault::$AdodbCharSet			 = 'WE8MSWIN1252';
	// CoreDefault::$AdodbCharSet			= "WE8ISO8859P1";	
	CoreDefault::$AdodbCharSet = "AL32UTF8";

	// Configs de conexão com SMTP para envio de emails
	//CoreDefault::$PhpmailerHost			= "mail.infoconsig.com.br";
	//CoreDefault::$PhpmailerPort			= 465;	
	//CoreDefault::$PhpmailerSMTPSecure	= "ssl";
	//CoreDefault::$PhpmailerMailer		= "smtp";
	//CoreDefault::$PhpmailerSMTPAuth		= true;
	//CoreDefault::$PhpmailerSMTPDebug	= false;
	//CoreDefault::$PhpmailerUsername		= "sttorico.infoconsig@infoconsig.com.br";
	//CoreDefault::$PhpmailerPassword		= "$%sçdfs04lk4kjGHG";	
	//CoreDefault::$PhpmailerFrom			= "sttorico.infoconsig@infoconsig.com.br";
  
    CoreDefault::$PhpmailerHost			= "smtp.umbler.com";
	CoreDefault::$PhpmailerPort			= 587;	
	CoreDefault::$PhpmailerSMTPSecure	= "tls";
	CoreDefault::$PhpmailerMailer		= "smtp";
	CoreDefault::$PhpmailerSMTPAuth		= true;
	CoreDefault::$PhpmailerSMTPDebug	= false;
	CoreDefault::$PhpmailerUsername		= "sttorico.infoconsig@infoconsig.com.br";
	CoreDefault::$PhpmailerPassword		= "lembrartrocar@";	
	CoreDefault::$PhpmailerFrom			= "sttorico.infoconsig@infoconsig.com.br";
  	
	CoreDefault::$PhpmailerFromName		= "Infoconsig";
	CoreDefault::$PhpmailerCharset		= 'utf-8';
	// Opções para SMTP sem verificação de certificado digital
	CoreDefault::$PhpmailerSMTPOptions	= array(
										'ssl' => array(
										        'verify_peer' => false,
										        'verify_peer_name' => false,
										        'allow_self_signed' => true
										    )
										);

	// Url do diretorio de arquivos do FCKeditor
	CoreDefault::$EditorUserPath		= CoreDefault::$DocumentUrl."img/editor/";
	// Caminho do diretorio de arquivos do FCKeditor
	CoreDefault::$EditorAbsolutePath = CoreDefault::$DocumentRoot."img/editor/";
	
	// Versão da biblioteca AdoDB : adodb ou adodb5.22.4
	CoreDefault::$ClassAdoDB = $ClassAdoDB;
	
	// Aplica configs
	CoreDefault::doRun();

	CoreDbTable::ExecSql("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");

	//por enquanto essas constantes não devem ter o nome alterado pois o Core esta Urilizando elas
	#ADM configs
	define('ADM_TITLE',							'..:: Infoconsig ::..');	// titulo da ADM
	
	//por enquanto essas constantes não devem ter o nome alterado pois o Core esta Urilizando elas
	# APP Config
	define('APP_INCLUDE',						CoreDefault::$DocumentRoot. $View . 'include/');
	
	define('APP_URL_PUBLIC',                    CoreDefault::$DocumentUrl. $View . 'public/');
	define('APP_PATH_PUBLIC',                   CoreDefault::$DocumentRoot. $View . 'public/');
	define('APP_PATH_TMP',                      CoreDefault::$DocumentRoot. $View . 'public/tmp/');
	
	define('APP_IMG_URL',						APP_URL_PUBLIC.'image/');   // Imagens php e outros por URL
	define('APP_SCRIPT_PATH',					APP_PATH_PUBLIC.'script/'); // Scripts php e outros por diretório lógico
	define('APP_IMG_PATH',						APP_PATH_PUBLIC.'image/' ); // Imagens php e outros por diretório lógico
	
	define('APP_CSS_URL',						APP_URL_PUBLIC."css/");
	define('APP_SCRIPT_URL',					APP_URL_PUBLIC."script/");

	//por enquanto essas constantes não devem ter o nome alterado pois o Core esta utilizando elas
	define('META_DESCRIPTION',					"");//
	define('META_ABSTRACT',						"");//
	define('META_KEYWORDS',						"");//
	define('META_RESOURCE_TYPE',				"document");//
	define('META_REVISIT_AFTER',				"5");//
	define('META_CLASSIFICATION',				"Gestão");//
	define('META_DISTRIBUTION',					"Private");//
	define('META_RATING',						"General");//
	define('META_AUTHOR',						"Sttorico Sistemas Ltda");//
	define('META_LANGUAGE',						"pt-br");//
	define('META_DOC_RIGHTS',					"Private");//
	define('ICO_FAVICON',						CoreDefault::$DocumentUrl.$View.$Adm."public/image/favicon.png");//
	define('SET_CHARSET',						"utf-8");//charset default do sistema
	define('SISTEM_TITLE',						'..:: Infoconsig :: Portal de Crédito Consignado ::..');	// titulo do sistema
	define('AUDIT_EDIT_DATA_GLOBAL',			false );// Registra auditoria edição de banco de dados a nível global
	define('PATH_DIR_ARQUIVOS_GERADOS',			CoreDefault::$DocumentRoot.$View."arquivos/");
	define('PATH_LINK_ARQUIVOS_GERADOS',		CoreDefault::$DocumentUrl."arquivos/");
	define('PATH_DIR_ARQ_REMESSA_SYS',	CoreDefault::$DocumentRoot.$View."remessaarquivos/");
	// Diretórios para os arquivos de remessa de fechamento de folha
	define('PATH_DIR_ARQ_REMESSA_XML_SYS',	PATH_DIR_ARQ_REMESSA_SYS."pastaArquivoXml/");
	define('PATH_DIR_ARQ_REMESSA_TXT_SYS',	PATH_DIR_ARQ_REMESSA_SYS."pastaArquivoTxt/");
	define('PATH_DIR_ARQ_REMESSA_EDI',		PATH_DIR_ARQ_REMESSA_SYS."pastaArquivoEdi/");
	define('PATH_DIR_ARQ_RECEBIDO',			CoreDefault::$DocumentRoot.$View."arquivosrecebidos/");
	define('PATH_DIR_GESTOR_DOCUMENTOS',	CoreDefault::$DocumentRoot.$View."gerenciardocumentos/arquivos/");
	define('PATH_DIR_RELATORIOS',           CoreDefault::$DocumentRoot.$View."relatorios/");
	define('URL_DIR_RELATORIOS',           CoreDefault::$ViewUrl."relatorios/");
	define('PATH_FONT_CARTAO_SERVFACIL',           CoreDefault::$CorePath.'Lib/Fonts/arialbd.ttf');
	define('PATH_CARTAO_SERVFACIL', APP_IMG_PATH.'cartaoservidor/');
	define('URL_CARTAO_SERVFACIL',  APP_IMG_URL.'cartaoservidor/');
	
	include_once APP_SCRIPT_PATH.'typesDefault.php';
	include_once APP_SCRIPT_PATH.'funcoesAuxiliares.php';
	
	define ( 'CoreToolsConfig', CoreDefault::$DocumentRoot );
	// Habilita o debug para mensagens de erro na gravação no banco de dados
	// - habilitar somente para captura de erros específicos até ser resolvido, depois desabilitar 
	// - para a produção manter desabilitado
	CoreDefault::$AdodbConnection->debug = false;

	$_SESSION['SES_IS_ALERT_AJAX'] = 1;
	$_SESSION['SET_CHARSET'] = SET_CHARSET;

	// Ano de início de informações
	$arrayAnos  = array();
	$anoInicial = 2013;
	// Para virada de final de ano
	$finalDeAno = (date("m") == 12) ? 1 : 0;
	while($anoInicial <= (date("Y") + $finalDeAno)){
		$arrayAnos[$anoInicial] = $anoInicial;
		$anoInicial++;
	}
		
	$dbAlternativoAudit = "mysql";
	$hostAuditDB = "";
	$userAuditDB = "";
	$nameAuditDB = "";
	$pwdAuditDB = "";
	if(isEmptyVars(array($hostAuditDB,$userAuditDB,$pwdAuditDB,$nameAuditDB))){
		$dbAlternativoAudit = null;
	}
	// Define se outro tipo de banco de dados será utilizado para armazenar a auditoria
	define('AUDIT_DB_TYPE',						"$dbAlternativoAudit");// Implementado para "mysql"			
	// Define se outro tipo de banco de dados será utilizado para armazenar a auditoria
	// $hostAuditDB:$userAuditDB:$pwdAuditDB:$nameAuditDB - conforme variavieis de banco de dados no arquivo pwd.inc.php
	define('AUDIT_HOST_DB',						"$hostAuditDB:$userAuditDB:$pwdAuditDB:$nameAuditDB");
	
	
	try {
		$svnSourceRevision = shell_exec("svn info ".CoreDefault::$DocumentRoot);
		if($svnSourceRevision){
			$svnRevision	= explode("\n", $svnSourceRevision);
			$varsSvn		= array("Revision:","Last Changed Author:","Last Changed Date:");
			$svnSession		= array("NR_VERSION_REVISION" => "","LAST_AUTHOR_REVISION" => "","LAST_DATE_REVISION" => "");
			foreach( $svnRevision as $row ){
				if (strpos( $row, $varsSvn[0] ) !== false) {
					$svnSession[ "NR_VERSION_REVISION" ] = trim(substr($row, strlen( $varsSvn[0] ) ));
				} else if (strpos( $row, $varsSvn[1] ) !== false) {
					$svnSession[ "LAST_AUTHOR_REVISION" ] = trim(substr($row, strlen( $varsSvn[1] ) ));
				} else if (strpos( $row, $varsSvn[2] ) !== false) {
					$svnSession[ "LAST_DATE_REVISION" ] = trim(substr($row, strlen( $varsSvn[2] ) ));
				}
			}
			$_SESSION[ PROJECT_FOLDER ]["VERSION"] = $svnSession;
		}
	} catch( Exception $e ){
		$_SESSION[ PROJECT_FOLDER ]["NR_VERSION_REVISION"]["MSG"]  = $e->getMessage(); 
	}
