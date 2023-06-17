<?php
	/**
	 * CoreDefault 
	 * @package Core
	 * @subpackage Src
	 * @author Otavio Theiss - iperfly@gmail.com
	 * @copyright 15/08/2010
	 */
	abstract class CoreDefault {
	    
	    /**
		 * Versão do Core
		 *
		 * @var string
		 */
		public static $CoreVersion = 	'1.00 Release 23/03/2011 ';
		
		/**
		 * Versão do Cms
		 *
		 * @var string
		 */
		public static $CmsVersion = 	'1.01 Release 05/09/2010 ';
		
		/**
		 * Abilita a ferramenta de gerar os Models
		 * @var bool
		 */
		public static $ToolsOn;
		
		/**
		 * Caminho fisico da Aplicação
		 *
		 * @var string
		 */
		public static $DocumentRoot;
		
		/**
		 * Caminho fisico do Cms
		 *
		 * @var string
		 */
		public static $CmsRoot;
		
		/**
		 * Url do Cms
		 *
		 * @var string
		 */		
		public static $CmsUrl;
		
		/**
		 * Url da Aplicação
		 *
		 * @var string
		 */
		public static $DocumentUrl;
		
	
		/**
		 * Caminho Fisico do Core
		 *
		 * @var string
		 */
		public static $CorePath;
		
		/**
		 * Url do Core
		 *
		 * @var string
		 */
		public static $CoreUrl;
		
		/**
		 * Caminho fisico do diretorio de idiomas
		 *
		 * @var string
		 */
		public static $LanguagePath;
		
		/**
		 * Caminho fisico das classes de Model
		 *
		 * @var string
		 */
		public static $ModelPath;
		
		/**
		 * Caminho fisico das classes de Controller
		 *
		 * @var string
		 */		
		public static $ControllerPath;
		/**
		 * Url do diretório View
		 * @var string
		 */
		public static $ViewUrl;
		
		/**
		 * Caminho fisico das classes de Controller do Cms
		 *
		 * @var string
		 */
		public static $ControllerCms;
		
		/**
		 * Link de Conexão com banco de dados
		 *
		 * @var resource
		 */
		public static $AdodbConnection;
		
		/**
		 * Host do banco de dados
		 *
		 * @var string
		 */
		public static $AdodbHost;
		
		/**
		 * Tipo de de banco de dados Exemplo("mysql" , "pgsql" .....)
		 *
		 * @var string
		 */
		public static $AdodbType;
		
		/**
		 * Usuario do banco de dados
		 *
		 * @var string
		 */
		public static $AdodbUsername;
		
		/**
		 * Senha do banco de dados
		 *
		 * @var string
		 */
		public static $AdodbPassword;
		
		/**
		 * Nome do banco de dados
		 *
		 * @var string
		 */
		public static $AdodbDatabase;
		
		/**
		 * Tipo de Caracter para banco de dados oracle
		 *
		 * @var string
		 */
		public static $AdodbCharSet;
		
		/**
		 * Host do servidor de SMTP
		 *
		 * @var string
		 */
		public static $PhpmailerHost;
		
		/**
		 * Porta de conexão SMTP default = 25 
		 *
		 * @var string
		 */
		public static $PhpmailerPort;
		
		/**
		 * Tipo de autenticação Tipos("smtp" ou "mail")
		 *
		 * @var string
		 */
		public static $PhpmailerMailer;
		
		/**
		 * Define se precisa autenticação
		 *
		 * @var bool
		 */
		public static $PhpmailerSMTPAuth;
		
		/**
		 * Usuario de SMTP
		 *
		 * @var string
		 */
		public static $PhpmailerUsername;
		
		/**
		 * Senha de SMTP
		 *
		 * @var string
		 */
		public static $PhpmailerPassword;
		
		/**
		 * Email do remetente de envio de email
		 *
		 * @var string
		 */
		public static $PhpmailerFrom;
		
		/**
		 * Nome do remetente de envio de email
		 *
		 * @var string
		 */
		public static $PhpmailerFromName;
		
		/**
		 * Configura o tipo de smtp seguro: "", "ssl" or "tls"
		 * @var string
		 */
		public static $PhpmailerSMTPSecure;
		
		/**
		 *  Sets SMTP class debugging on or off.
		 * @var bool
		 */
		public static $PhpmailerSMTPDebug;
		
		/**
		 * Set config for SMTP authenticate with/without certificates SSL
		 * @var array
		 */
		public static $PhpmailerSMTPOptions;
		/**
		 * Set the charset of body
		 * @var string
		 */
		public static $PhpmailerCharset;
		
		/**
		 * Url do diretorio de arquivos do FCKeditor
		 *
		 * @var string
		 */
		public static $EditorUserPath;
		
		/**
		 * Caminho fisico do diretorio de arquivos do FCKeditor
		 *
		 * @var string
		 */
		public static $EditorAbsolutePath;
		
		/**
		 * Controle para carregamento do javascript do Core
		 *
		 * @var bool
		 */
		private static $JsCore;
		
		/**
		 * Controle para carregamento do javascript do componente FCKeditor
		 *
		 * @var bool
		 */
		private static $JsEditor;

		/**
		 * Controle para carregamento do javascript do componente FCKeditor
		 *
		 * @var bool
		 */
		private static $JsCKEditor;
		
		/**
		 * Controle para carregamento do javascript do Componente de calendario
		 *
		 * @var string
		 */
		private static $JsCalendar;
		
		/**
		 * Controle para carregamento do javascript do Componente de SwfObject
		 *
		 * @var bool
		 */
		private static $JsSwfObject;
		
		/**
		 * Controle para carregamento do Css do Componente de Tabs
		 *
		 * @var bool
		 */
		private static $CssGooxTabs;
		
		public static $ClassAdoDB = 'adodb';		
		
		/**
		 * Caminhos das classe do Core e componentes do Core
		 *
		 * @var array
		 */
		private static $CoreClasses = array(
			 "CoreDbConnection" 	=> "Src/CoreDbConnection.php"
			,"CoreDbField"			=> "Src/CoreDbField.php"
			,"CoreDbTable" 			=> "Src/CoreDbTable.php"
			,"CoreDefault"			=> "Src/CoreDefault.php"
			,"CoreCurrency"			=> "Src/CoreCurrency.php"
		    ,"CoreMail"				=> "Src/CoreMail.php"
			,"CoreError" 			=> "Src/CoreError.php"
			,"CoreFunctions" 		=> "Src/CoreFunctions.php"
			,"CoreLanguage" 		=> "Src/CoreLanguage.php"
			,"CoreRouters"			=> "Src/CoreRouters.php"
			,"CoreThumb"			=> "Src/CoreThumb.php"
			,"CoreToolsOop"			=> "Src/CoreToolsOop.php"
			,"CoreWebAjax" 			=> "Src/CoreWebAjax.php"
			,"CoreWebSecurity" 		=> "Src/CoreWebSecurity.php"
			,"CoreWebUiControls"	=> "Src/CoreWebUiControls.php"
			,"CoreWebUiForm" 		=> "Src/CoreWebUiForm.php"
			,"CoreWebUiGrid" 		=> "Src/CoreWebUiGrid.php"
			,"CorePagination" 		=> "Src/CorePagination.php"
			,"CoreWebMetaTag"		=> "Src/CoreWebMetaTag.php"
			,"CoreLdap"				=> "Src/CoreLdap.php"
			
			,"CoreLibGenericUpload" => "Src/CoreLibGenericUpload.php"
			,"CoreLibJQuery"		=> "Src/CoreLibJQuery.php"		
		    ,"ADOFieldObject"		=> "Lib/adodb/adodb.inc.php"
// 		    ,"ADOFieldObject"		=> "Lib/".CoreDefault::$ClassAdoDB."/adodb.inc.php"
// 		    ,"ADOConnection"		=> "Lib/".CoreDefault::$ClassAdoDB."/adodb.inc.php"
// 		    ,"ADOFetchObj"			=> "Lib/".CoreDefault::$ClassAdoDB."/adodb.inc.php"
// 		    ,"ADORecordSet_empty"	=> "Lib/".CoreDefault::$ClassAdoDB."/adodb.inc.php"
// 		    ,"ADORecordSet"			=> "Lib/".CoreDefault::$ClassAdoDB."/adodb.inc.php"
// 		    ,"ADORecordSet_array"	=> "Lib/".CoreDefault::$ClassAdoDB."/adodb.inc.php"
		    		    
			,"nusoap_base"			=> "Lib/nusoap/lib/nusoap.php"
			,"nusoap_fault"			=> "Lib/nusoap/lib/nusoap.php"
			,"soap_fault"			=> "Lib/nusoap/lib/nusoap.php"
			,"nusoap_xmlschema"		=> "Lib/nusoap/lib/nusoap.php"
			,"XMLSchema"			=> "Lib/nusoap/lib/nusoap.php"
			,"soapval"				=> "Lib/nusoap/lib/nusoap.php"
			,"soap_transport"		=> "Lib/nusoap/lib/nusoap.php"
			,"nusoap_server"		=> "Lib/nusoap/lib/nusoap.php"
			,"soap_server"			=> "Lib/nusoap/lib/nusoap.php"
			,"wsdl"					=> "Lib/nusoap/lib/nusoap.php"
			,"nusoap_parser"		=> "Lib/nusoap/lib/nusoap.php"
			,"soap_parser"			=> "Lib/nusoap/lib/nusoap.php"
			,"nusoap_client"		=> "Lib/nusoap/lib/nusoap.php"
			,"soapclient"			=> "Lib/nusoap/lib/nusoap.php"
			,"CoreSendGrid"         => "Lib/sendgrid/CoreSendGrid.php"
		    ,"TCPDF"                => "Lib/tcpdf/tcpdf.php"
		    ,"FPDF"                 => "Lib/FPDF/fpdf.php"
		    ,"PDF_Code39"           => "Lib/FPDF/fpdf_bar39.php"
		    ,"CaptchaSys"           => "Lib/CaptchaSys/CaptchaSys.php"
		    ,"Mobile_Detect"        => "Lib/Mobile_Detect/Mobile_Detect.php"
            
			//,"FCKeditor"			=> "Lib/fckeditor/fckeditor.php"
			
			//,"bip2"					=> "Lib/easyzip/EasyBzip2.class.php"
			//,"archive"				=> "Lib/easyzip/EasyArchive.class.php"
			//,"gzip"					=> "Lib/easyzip/EasyGzip.class.php"
			//,"tar"					=> "Lib/easyzip/EasyTar.class.php"
			//,"zip"					=> "Lib/easyzip/EasyZip.class.php"
			
			//,"QRCodeGoogle"			=> "Lib/qrcode_google/qrcode.php"
			//,"QRCodeImage"			=> "Lib/QRCode/Image/QRCode.php"
			
			//,"aFiles"				=> "Lib/afiles/afiles.class.php"
			//,"SwfObject"			=> "Lib/goox_swfobject/class/goox_swfobject.class.php"
			//,"GooxTabs"				=> "Lib/goox_tabs/goox_tabs.class.php"
			//,"gooxBox"				=> "Lib/gooxBox/gooxBox.class.php"
			//,"gooxDialog"			=> "Lib/dialog/goox_dialog.class.php"
			//,"GenericUpload"		=> "Lib/generic_upload/generic_upload.class.php"
			//,"ForceDownload"		=> "Lib/force_download/force_download.class.php"
			
		);
		
		/**
		 * Retorna versão atual do Core
		 *
		 * @return string
		 */
		public static function getCoreVersion(){
			return CoreDefault::$CoreVersion;
		}
				
		/**
		 * Retorna versão atual do Cms
		 *
		 * @return string
		 */
		public static function getCmsVersion(){
			return CoreDefault::$CmsVersion;
		}
		
		/**
		 * Verifica se é uma classe do Core
		 *
		 * @param string $class
		 * @return boolean
		 */
		public static function isCoreClass($class) {
			return (isset(CoreDefault::$CoreClasses[$class]));
		}
	
		/**
		 * Verifica se é um classe do Model da aplicação
		 *
		 * @param string $class
		 * @return boolean
		 */
		public static function isModelClass($class) {
			return (file_exists(CoreDefault::$ModelPath.$class.'.php'));
		}

		/**
		 * Verifica se é um classe do Controller da aplicação
		 *
		 * @param string $class
		 * @return boolean
		 */
		public static function isControllerClass($class) {
			return (file_exists(CoreDefault::$ControllerPath.$class.'.php'));
		}

		/**
		 * Verifica se é um classe do Controller do Cms
		 *
		 * @param string $class
		 * @return boolean
		 */
		public static function isControllerCms($class) {
			return (file_exists(CoreDefault::$ControllerCms.$class.'.php'));
		}
		
		
		/**
		 * Retorna o caminho fisico da classe do Core
		 *
		 * @param string $class
		 * @return string
		 */
		public static function getCoreClass($class) {
		    return (isset(CoreDefault::$CoreClasses[$class]) ? CoreDefault::$CorePath.CoreDefault::$CoreClasses[$class] : "");
		}
	
		/**
		 * Retorna o caminho fisico da classe de Model 
		 *
		 * @param string $class
		 * @return string
		 */
		public static function getModelClass($class) {
			return CoreDefault::$ModelPath.$class.'.php';
		}

		/**
		 * Retorna o caminho fisico da classe de Controller 
		 *
		 * @param string $class
		 * @return string
		 */
		public static function getControllerClass($class) {
			return CoreDefault::$ControllerPath.$class.'.php';
		}
		
		/**
		 * Retorna o caminho fisico da classe de Controller do Cms 
		 *
		 * @param string $class
		 * @return string
		 */
		public static function getControllerCms($class) {
			return CoreDefault::$ControllerCms.$class.'.php';
		}
		
		/**
		 * Carrega as informações iniciais
		 *
		 * @param array $array
		 * @return boolean
		 */
		public static function doRun() {
			if (!CoreDefault::isCoreLoaded())
				return new CoreError(1);
			$db = new CoreDbConnection();
			if (!CoreDefault::isConnectionLoaded())
				return new CoreError(2);
		}
	
		/**
		 * Verifica se carregou o Core corretamente
		 *
		 * @return boolean
		 */
		public static function isCoreLoaded() {
			return (file_exists(CoreDefault::$CorePath) && CoreDefault::$CoreUrl);
		}
	
		/**
		 * Verifica se conseguiu conectar no banco de dados
		 *
		 * @return boolean
		 */
		public static function isConnectionLoaded() {
			return (CoreDefault::$AdodbConnection);
		}
		
		/**
		 * Veirfica se carregou o css do Componente de tabs
		 *
		 * @return boolean
		 */
		public static function isCssGooxTabs() {
			return CoreDefault::$CssGooxTabs;
		}
	
		/**
		 * Verifica se existe o diretorio de idiomas 
		 *
		 * @deprecated 
		 * @return boolean
		 */
		public static function isLanguageLoaded() {
			return (!CoreDefault::$LanguagePath || file_exists(CoreDefault::$LanguagePath));
		}
		
		/**
		 * Retorna o caminho fisico da biblioteca adodb
		 *
		 * @return string
		 */
		public static function getAdodbPath() {
			
		    if (!CoreDefault::isCoreLoaded())
				return new CoreError(1);
		    
			//return CoreDefault::$CorePath."Lib/adodb/adodb.inc.php";
			//return CoreDefault::$CorePath."Lib/".CLASS_ADODB."/adodb.inc.php";
			return CoreDefault::$CorePath."Lib/".CoreDefault::$ClassAdoDB."/adodb.inc.php";
		}
	
	
		/**
		 * Retorna scripts da biblioteca jscalendar
		 * 
		 * @param string $theme Tema do calendário
		 * @param string $lang Idioma do calendário
		 *
		 * 
		 */
		public static function getJsCalendar($theme='aqua', $lang='en') {
			if (!CoreDefault::isCoreLoaded())
				return new CoreError(1);
			$s = '
	<script src="'.CoreDefault::$CoreUrl.'Lib/jscalendar/calendar.js"></script>
	<script src="'.CoreDefault::$CoreUrl.'Lib/jscalendar/lang/calendar-'.$lang.'.js"></script>
	<script src="'.CoreDefault::$CoreUrl.'Lib/jscalendar/calendar-setup.js"></script>
	<link rel="stylesheet" media="all" href="'.CoreDefault::$CoreUrl.'Lib/jscalendar/skins/'.$theme.'/theme.css" />
	';
			CoreDefault::$JsCalendar = true;
			return $s;
		}
		
		/**
		 * Retorna scripts da biblioteca swfobject do google
		 * 
		 * 
		 */
		public static function getJsSwfObject() {
			if (!CoreDefault::isCoreLoaded())
				return new CoreError(1);
			$s = '
	<script src="'.CoreDefault::$CoreUrl.'Lib/goox_swfobject/lib/swfobject.js"></script>';
			CoreDefault::$JsSwfObject = true;
			return $s;
		}
	
		/**
		 * Retorna scripts do Core
		 * 
		 * 
		 */
		public static function getJsCore() {
			if (!CoreDefault::isCoreLoaded())
				return new CoreError(1);
			//  "CoreWebUiScript.js" agora está no HEADER.INC.PHP por causa de compatibilidade com versões do Internet Explorer ao fazer o parser com eval do arquivo nos corpo da página
			$s = '<link rel="stylesheet" media="all" href="'.CoreDefault::$CoreUrl.'Public/CoreWebUiStyle.css" />';
			CoreDefault::$JsCore = true;
			return $s;
		}
	
		/**
		 * Retorna scripts do fckeditor
		 * 
		 * 
		 */
		public static function getJsEditor() {
			if (!CoreDefault::isCoreLoaded())
				return new CoreError(1);
			$s = '
	<script src="'.CoreDefault::$CoreUrl.'Lib/fckeditor/fckeditor.js"></script>';
			CoreDefault::$JsEditor = true;
			return $s;
		}
		
		/**
		 * Retorna scripts do fckeditor
		 * 
		 * 
		 */
		public static function getJsCKEditor() {
			if (!CoreDefault::isCoreLoaded())
				return new CoreError(1);
			$s = '
	<script src="'.CoreDefault::$CoreUrl.'Lib/ckeditor/ckeditor/ckeditor.js"></script>
	<script src="'.CoreDefault::$CoreUrl.'Lib/ckeditor/ckfinder/ckfinder.js"></script>';
			CoreDefault::$JsCKEditor = true;
			return $s;
		}
		
		/**
		 * Verifica se o js do Core foi carregado
		 * 
		 * @return boolean
		 */
		public static function isJsCoreLoaded() {
			return CoreDefault::$JsCore;
		}
		
		/**
		 * Verifica se o js do editor foi carregado
		 * 
		 * @return boolean
		 */
		public static function isJsEditorLoaded() {
			return CoreDefault::$JsEditor;
		}
	
		/**
		 * Verifica se o js do editor foi carregado
		 * 
		 * @return boolean
		 */
		public static function isJsCKEditorLoaded() {
			return CoreDefault::$JsCKEditor;
		}
		
		/**
		 * Verifica se o js do calendário foi carregado
		 * 
		 * @return boolean
		 */
		public static function isJsCalendarLoaded() {
			return CoreDefault::$JsCalendar;
		}
		
		/**
		 * Verifica se o swfobject.js foi carregado
		 * 
		 * @return boolean
		 */
		public static function isJsSwfObjectLoaded() {
			return CoreDefault::$JsSwfObject;
		}

		/**
		 * Função para debugar objeto com print_r ou var_dump
		 * @param mixed $Str=null - objeto a ser debugado
		 * @param boolean $Dump=false - retorna dump do objeto
		 * @param boolean $Stop=true - para execução do script
		 * 
		 * @return mixed
		 */
		public static function doDebug($Str=null,$Dump = false,$Stop = true) {
			echo "<pre>"; 
			if($Dump)
				var_dump($Str);
			else
				print_r($Str);
			echo "</pre>";
			if ($Stop)
				die;
		}
		

	}
