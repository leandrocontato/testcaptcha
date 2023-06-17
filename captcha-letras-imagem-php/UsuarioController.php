<?php
/**
 * UsuarioController
 * @package Usuarios
 * @subpackage Sessao
 * @author Adriano Vanderlinde - adriano.v@datainfo.inf.br
 * @copyright 24/11/2010
 */
class UsuarioController extends CoreDbTable {
	
	public function __construct(){ }
		
    /**
	 * Executa o login
	 * @param array $post
	 */
	public static function doLogin($post){
		$msgLogin = "";
		if(isset($_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"])){
			unset($_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"]);
		}
		
		$tentativasAcesso = isset($_SESSION[PROJECT_FOLDER]['tentativasAcesso']) ? $_SESSION[PROJECT_FOLDER]['tentativasAcesso'] : 0;		
					
		$Usuario 	= new UsuarioModel();
		$Security 	= new CoreWebSecurity();
		$Function 	= new CoreFunctions();
		// Tratamento para evitar SqlInjection
		$post['DS_LOGIN']	= preg_replace(("/(from|select|insert|delete|where|drop|alter table|show tables|#|\*|--|\\\\)/"),"",$post['DS_LOGIN']);
		$post['DS_LOGIN']	= trim($post['DS_LOGIN']);
		$post['senha']		= trim($post['senha']);
		$post['DS_LOGIN']	= strip_tags($post['DS_LOGIN']);
		$post['DS_LOGIN']	= addslashes($post['DS_LOGIN']);
        $primeiroAcesso     = isset($post["PRIMEIRO_ACESSO"]) ? $post["PRIMEIRO_ACESSO"] : false;
		
		$sql = "select cd_usuario from usuario where regexp_replace( ds_login, '".CHAR_IGNORADO_LOGIN."') = regexp_replace( '{$post['DS_LOGIN']}', '".CHAR_IGNORADO_LOGIN."') and ds_senha = '{$Security->doMd5($post['senha'])}'";
		
		$rsUsuario = self::ExecSql($sql);
		
		$byPass = true;
		
		$codUsuario = isset($rsUsuario->fields["CD_USUARIO"]) ? $rsUsuario->fields["CD_USUARIO"] : null;
		$msgUsuarioSenhaIncorreto = "[Usuário ou Senha incorreto!]";
		
		//Confirmação adicional - sempre verdadeira
		if($codUsuario){
			
			$Usuario->setCD_USUARIO( $codUsuario );
			//$Usuario->setDebug(true);
			$Usuario->doFind();
			
			if(!$primeiroAcesso){
				
				if($Usuario->getID_ATIVO() == 0){
					
					$byPass = false;
					
					if($Usuario->getDS_MOTIVO_BLOQUEIO() != ''){
						$msgLogin = $Usuario->getDS_MOTIVO_BLOQUEIO();
					} else {
						// Mensagem exibida por questões de segurança mesmo o usuário e senha estarem corretos mas desabilitados no sistema
						$msgLogin = $msgUsuarioSenhaIncorreto;
					}
					
				} else {
					
					// Retira os caracteres ignorados no login
					$user1 = preg_replace("/".CHAR_IGNORADO_LOGIN."/", "", $Usuario->getDS_LOGIN());
					$user2 = preg_replace("/".CHAR_IGNORADO_LOGIN."/", "", $post['DS_LOGIN']);
					
					if( $user1 == $user2 && $Usuario->getDS_SENHA() == $Security->doMd5($post['senha'])){
					    						
						//Verifica quanto tempo o usuario esta inativo
						if($Usuario->getCD_GRUPO() == 48){
							
							$dtDesbloqueio		= $Usuario->getDT_DESBLOQUEIO();
							
  							// Verifica se tem verificação por inatividade de acesso conforme o consignante
	  						$sql = "SELECT p.cd_consignante, p.nr_dia_inatividade FROM prm_consignante p WHERE p.nr_dia_inatividade > 0 and p.cd_consignante IN ( select distinct cd_consignante from usuario_acesso where cd_usuario = $codUsuario )";
	  						$rsPrmInatividadeAcesso =  self::ExecSql($sql);
	  						if($rsPrmInatividadeAcesso->_numOfRows > 0){
								// Verifica se no consignante onde a pessoa está como servidor tem restrição por inatividade de acesso
								$dtAcessoAnterior	= $Usuario->getDT_ULTIMO_ACESSO();
								
								// Entre a data do acesso anterior e desbloqueio pega-se a data mais recente
								if($dtDesbloqueio>$dtAcessoAnterior){
									$diasSemAcesso 		= diffDate( $dtDesbloqueio, $dtAcessoAtual, "D");
								} else {
									$diasSemAcesso 		= diffDate( $dtAcessoAnterior, $dtAcessoAtual, "D");
								}
		  						$dtAcessoAtual		= date("YmdHi");
		  						
		  						foreach ( $rsPrmInatividadeAcesso->_array as $rowPrmAcesso ){
		  							$nrDiasInatividade	= $rowPrmAcesso["NR_DIA_INATIVIDADE"];
		  							$codConsignante		= $rowPrmAcesso["CD_CONSIGNANTE"]; 
		  							if( $nrDiasInatividade > 0){ 
		  								if( $diasSemAcesso > $nrDiasInatividade ){
		  									// O bloqueio inicia ou permanece
			  								$dsConsignante = ConsignanteController::getValueFieldNameCng( $codConsignante , "nm_fantasia" );
			  								$dsMsgInatividade = "[Usuário foi bloqueado devido a inatividade de \"$nrDiasInatividade\"($dsConsignante) dias no Infoconsig. Favor entrar em contato com o suporte técnico para que o desbloqueio do usuário seja realizado.]";
			  								$msgLogin .= "[$dsMsgInatividade]";
			  								//self::doAtivaInativa($Usuario->getCD_USUARIO(), 0);
			  								//self::doAlteraMsgBloqueio($Usuario->getCD_USUARIO(), $dsMsgInatividade);
			  								$byPass = false;		  									
		  								}
		  							}
		  						}
		  						
		  						//if($byPass && $dtDesbloqueio>0 ){
		  						//	// Mas houve um bloqueio anterior e então desbloqueio - retira a data de desbloqueo e mensagens
		  						//	self::doDeleteMotivoDtDesbloqueio($Usuario->getCD_USUARIO());
		  						//}
	  						}
	  						
	  						$restricaoIpController = new RestricaoIpController();
	  						$restricaoIp = $restricaoIpController->doValidaRestricaoIp($Usuario->getCD_USUARIO(),$_SERVER["REMOTE_ADDR"]);
							if($restricaoIp){
								$msgLogin .= '[Este endereço IP não está autorizado pelo sistema ! Por favor entre em contato com o seu RH.]';
								$byPass = false;
							}
							//if($byPass && $dtDesbloqueio>0 ){
							//	// Mas houve um bloqueio anterior e então desbloqueio - retira a data de desbloqueo e mensagens
							//	self::doDeleteMotivoDtDesbloqueio($Usuario->getCD_USUARIO());
							//}
						}						
						
						//Validação sobre Email valido pelo dominio Consignataria.
						$colaboradorConsignataria = new ColaboradorConsignatariaController();
						if( !$colaboradorConsignataria->doValidaDominioColaborador($Usuario->List->fields,true)){
							$byPass = false;
							$msgLogin .= '[Login não autorizado, favor contatar nosso suporte - Dominio não autorizado.]';
						}
						//Fim validação Dominio

						//Validação Restrição ip Consignataria 
						$restricaoIp = new RestricaoIpController();
						if(!$restricaoIp->doValidaRestricaoIpConsignataria($Usuario->getCD_USUARIO())){
							$byPass = false;
							$msgLogin .= '[Login não autorizado, favor contatar nosso suporte - IP não cadastrado.]';
						}
						//Fim Validação Restrição ip Consignataria
						
						if($byPass){
                            if( $tentativasAcesso >= 3 ){                                
                                if( !CaptchaSys::verifyCodeCaptcha($post['code']) ){
                                    $byPass = false;
                                    $_SESSION[ session_id() ]["CaptchaSys"]["msg"] = "As letras não foram digitadas corretamente!";
                                } else {
                                    unset( $_SESSION[PROJECT_FOLDER]['tentativasAcesso'] );
                                    $tentativasAcesso = 0;                                    
                                }
							}
						}
					}
				}
			}
		} else {
			$byPass = false;
			
			$Usuario->setDS_LOGIN($post['DS_LOGIN']);
			//$Usuario->setDebug(true);
			$Usuario->doFind();
			
			// Tentativas sucessivas de validação para usuário legítimo, apenas a senha foi esquecida
			if($Usuario->getCount() > 0){
				// Usuário foi encontrado
				if ($Usuario->getID_ATIVO() == 0) {
					// Mas está inativo e se houver motivo de bloqueio recupera a mensagem
					if($Usuario->getDS_MOTIVO_BLOQUEIO() != ''){
						$msgLogin .= $Usuario->getDS_MOTIVO_BLOQUEIO();						
					} else{
						// Mensagem padrão
						$msgLogin .= $msgUsuarioSenhaIncorreto;						
					}
				} else {
					
				    $msgLogin = $msgUsuarioSenhaIncorreto;
					
					if( 2 <= $tentativasAcesso){
						// A partir da segunda tentativa invalida sugere troca de senha
						$msgLogin .= "[<a href=\"".CoreDefault::$DocumentUrl."?EsqueceuSuaSenha=1\" class=\"bt-recupera-senha\">Esqueceu o seu usuário ou senha? Clique aqui.</a>.]";
					}
					    
					$sql = "select distinct ua.cd_consignante from usuario_acesso ua, consignante cng where ua.cd_usuario = ".$Usuario->getCD_USUARIO()." AND ua.cd_consignante = cng.cd_consignante AND cng.ativo_administrador = 1";
					$rsTesteNrTentativaBloqueio = self::ExecSql($sql);
					
					if($rsTesteNrTentativaBloqueio->_numOfRows > 0){
					    // Testa a quantidade de tentativas permitidas até limite configurado em cada consignante. Se ultrapassar o limite em alguns dos consignantes bloqueia o usuário de modo geral.
					    foreach($rsTesteNrTentativaBloqueio->_array as $row){							
					        if(PrmConsignanteController::getIsAtivoBloqueioLogin($row['CD_CONSIGNANTE'])){								
					            if(  PrmConsignanteController::getNrTentativasBloqueioLogin( $row['CD_CONSIGNANTE']) <= $tentativasAcesso){
									$dsMsgBloqueioTentativa = "[Usuário bloqueado devido ao número de tentativas inválidas. Favor entrar em contato com o suporte técnico para que o desbloqueio do usuário seja realizado.]"; 
									$msgLogin .= $dsMsgBloqueioTentativa;
									break;
								}								
							}
						}
					}					
				}
			} else {
				$msgLogin = $msgUsuarioSenhaIncorreto;
			}
		}
		
		if ( (isset($dsMsgInatividade) && $dsMsgInatividade) || (isset($dsMsgBloqueioTentativa) && $dsMsgBloqueioTentativa) ){
			self::doAtivaInativa($Usuario->getCD_USUARIO(), 0);
			self::doAlteraMsgBloqueio($Usuario->getCD_USUARIO(), $dsMsgInatividade . $dsMsgBloqueioTentativa );
		}
		
		if($msgLogin){
			$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = str_replace(array("][","[","]"), array("<br />","",""), $msgLogin);				
		}
		
		if(!$byPass){
			// Contador geral para número de tentativas.
		    $tentativasAcesso++;			
		}
		
		if( $byPass ){
			// Começa a registrar sessão mas não foi concluído
			$rsPessoa    = PessoaController::getPessoaByCod( $Usuario->getCD_PESSOA() );
			$_SESSION[PROJECT_FOLDER]["AUTH"]["NM_USUARIO"]	= $rsPessoa->getNM_PESSOA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]['CD_USUARIO'] = $Usuario->getCD_USUARIO();
			$_SESSION[PROJECT_FOLDER]["AUTH"]['DS_LOGIN']	= $Usuario->getDS_LOGIN();
			$_SESSION[PROJECT_FOLDER]["AUTH"]['CD_GRUPO']	= $Usuario->getCD_GRUPO();
			$_SESSION[PROJECT_FOLDER]["AUTH"]['CD_PERFIL']	= $Usuario->getCD_PERFIL();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["MUDAR_SENHA"]= $Usuario->getATIVO_MUDA_SENHA();
			
			//$dtAcessoAtual = date("YmdHis");
			//$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_INICIO_ACESSO_ATUAL"] = $dtAcessoAtual;
			//if ($Usuario->getDT_ULTIMO_ACESSO()) {
			//  $_SESSION[PROJECT_FOLDER]["AUTH"]["DT_INICIO_ACESSO_ATUAL"] = $function->db2Date( $dtAcessoAtual , 'd/m/Y').' às '. $function->db2Date( $dtAcessoAtual ,'H:i:s');
			//	$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"] 	= $Function->db2Date( $Usuario->getDT_ULTIMO_ACESSO() , 'd/m/Y').' às '. $Function->db2Date( $Usuario->getDT_ULTIMO_ACESSO() ,'H:i:s');
			//} else {
			//	$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"] 	= null;
			//}
			
			// Passou na verificações básicas - Agora é realizada outras validações
			// Validação para primeiro do servidor ou forçado pelo administrado
			$validacoesObrigatorias = ( ( in_array($Usuario->getCD_GRUPO(),array(48)) && !$Usuario->getDT_ULTIMO_ACESSO() ) || $Usuario->getATIVO_MUDA_SENHA()) ? true : false;
			// Validacoes
			
			if($validacoesObrigatorias){
				// Ainda não será registrado o login por meio da função 
				$_SESSION[PROJECT_FOLDER]["AUTH"]["VALIDACOES"] = true;
				$byPass = false;
			} else {
				//unset($_SESSION[PROJECT_FOLDER]["AUTH"]["VALIDACOES"]);
                $_SESSION[PROJECT_FOLDER]["AUTH"]["VALIDACOES"] = false;
			}			
		}
		
		if( $byPass ){
			
			unset( $_SESSION['captcha'] );
			unset( $_SESSION[PROJECT_FOLDER]['tentativasAcesso'] );
			$tentativasAcesso = 0;
			
			$statusSessao = self::online("login");
						
			if(!$statusSessao["status"]){
				// Se passar na função anterior então está ok
				self::setSessionLogin($Usuario);
				if( array_key_exists("REDIRECT_URL", $_SERVER) ){
					$url = $_SERVER["HTTP_REFERER"];
					CoreFunctions::doRedirectUrl($url);
					exit;
				}
			}			
		} else {
		    // Se não passou em qualquer uma das verificações volta a memória da sessão
    		$_SESSION[PROJECT_FOLDER]['tentativasAcesso'] = $tentativasAcesso;		    
		}
	}
	
	/**
	 *
	 * Desconecta usuario do CMS
	 */
	public static function doLogoff(){
	    $off = isset($_GET["off"]) ? $_GET["off"] : null; // Fazer encerramento de conexões abertas
		if($off){
			$security = new CoreWebSecurity();
			$off = $security->getOut($off);
			$off = explode(";", $off);
			$codUsuario		= $off[0];
			$dtFimSessoes	= $off[1];
			if($codUsuario){
				$dtFimSessoes	= DateTime::createFromFormat('YmdHi', $dtFimSessoes );
				$dtAtual 		= DateTime::createFromFormat('YmdHi', date("YmdHi"));
				$nrTempoIntervalo = $dtFimSessoes->diff( $dtAtual );
				
				// Em dias
				$dif = $nrTempoIntervalo->d; // dia 
				if($dif == 0) { 
					// Possivel apenas no período de 24 horas
					$sql = "update usuario set id_status = 0 where cd_usuario = $codUsuario and id_status = 1 ";
					self::ExecSql($sql);
					//self::unsetSessionLogin();
					$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "Todas as conexões anteriormente abertas foram encerradas. Tente novamente.";				
				} else {
					// Acima de 24 horas o link de encerramento de conexões em outros dipositivos não pode ser mais utilizado
					$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "Uso de sessão incorreto. Contacte o administrador do sistema.";
				}
			}
		} else {
			if( isset($_SESSION[PROJECT_FOLDER]["AUTH"]["CPF"]) && $_SESSION[PROJECT_FOLDER]["AUTH"]["CPF"] == 1){
			    if( isset($_SESSION[PROJECT_FOLDER]["AUTH"]["USUARIOS"]) ){
			        $sessao = $_SESSION[PROJECT_FOLDER]["AUTH"]["USUARIOS"];
			        foreach ( $sessao as $codPessoa => $acesso ){
			            foreach ($acesso["ACESSO"] as $index => $usuario){
			                $sql = " update usuario set id_status = 0 where id_status = 1 and cd_usuario = {$usuario["codUsuario"]}";
			                self::ExecSql($sql);
			            }
			        }
			    }			    			
			} else {
			    $usuario 		= new UsuarioModel();
			    $usuario->doClearFields();
			    $usuario->setCD_USUARIO($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_USUARIO"]);
			    $usuario->doFind();
			    
			    $usuario->setID_STATUS(0);
			    $usuario->doUpdate();
			}
			self::unsetSessionLogin();			
		}
		
        CoreFunctions::doRedirectUrl( CoreDefault::$DocumentUrl );
		exit;
	}

	/**
	 * Atribui valor a session de autenticação e registra no banco os dados da sessão. 
	 * Antes de ser executada todas as validações exigidas para o tipo de usuário devem ter resultado positivo.
	 * @param $user UsuarioModel 
	 * @param $acesso array
	 */
	private static function setSessionLogin($user, $acesso = null){
		
		$function = new CoreFunctions();
		
		$Pessoa      = new PessoaController();
		$rsPessoa    = $Pessoa->getPessoaByCod($user->getCD_PESSOA());
		
		$_SESSION[PROJECT_FOLDER]["AUTH"]["NM_USUARIO"]	= $rsPessoa->getNM_PESSOA();
		$_SESSION[PROJECT_FOLDER]["AUTH"]['CD_USUARIO'] = $user->getCD_USUARIO();
		$_SESSION[PROJECT_FOLDER]["AUTH"]['DS_LOGIN']	= $user->getDS_LOGIN();
		$_SESSION[PROJECT_FOLDER]["AUTH"]['CD_GRUPO']	= $user->getCD_GRUPO();
		$_SESSION[PROJECT_FOLDER]["AUTH"]['CD_PERFIL']	= $user->getCD_PERFIL();
		
		if($acesso){
			$_SESSION[PROJECT_FOLDER]["AUTH"]['DS_LOCAL']	= $acesso["dsLocal"];
			$_SESSION[PROJECT_FOLDER]["AUTH"]['DS_LOGIN']	= $acesso["dsLogin"]; // Mais informações no login
		}
		
		//SERVIDOR
		if ($user->getCD_GRUPO() == "48") {
			if( isset($_SESSION[ PROJECT_FOLDER ]["AUTH"]["CPF"]) && $_SESSION[PROJECT_FOLDER]["AUTH"]["CPF"] == 1){
				if($acesso){
					// Por CPF - a sessão é definida com a matrícula previamente escolhida
					$codConsignanteMaster = $acesso["codConsignanteMaster"];
					$codConsignante = $acesso["codConsignanteMaster"];
					$codAverbador	= $acesso["codAverbador"];
					$codServidor	= $acesso["codServidor"];
					
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE_MASTER"] = $codConsignanteMaster;
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE"]        = $codConsignante;
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_AVERBADOR"]          = $codAverbador;
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_SERVIDOR"]           = $codServidor;
				}				
			} else {
				// Verifica quantas matrículas com acesso possível existem 
				$rsMatriculas = self::getUsuarioMatServidor($user->getCD_USUARIO());
				if($rsMatriculas->_numOfRows>1){
					// Escolher na próxima tela
					$_SESSION[PROJECT_FOLDER]["AUTH"]["ESCOLHER"]			= 1;					
				} elseif($rsMatriculas->_numOfRows==1){ 
					// define a sessão com a única matrícula possível encontrada
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE_MASTER"] = $rsMatriculas->fields["CD_CONSIGNANTE_MASTER"];
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE"]        = $rsMatriculas->fields["CD_CONSIGNANTE"];
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_AVERBADOR"]          = $rsMatriculas->fields["CD_AVERBADOR"];
					$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_SERVIDOR"]           = $rsMatriculas->fields["CD_SERVIDOR"];
					$codServidor = $rsMatriculas->fields["CD_SERVIDOR"];
				} else { 
					unset($_SESSION[PROJECT_FOLDER]["AUTH"]);
					$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "Usuário não encontrado!(1)";
				}
			}
			
			if( isset($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_SERVIDOR"]) && $_SESSION[PROJECT_FOLDER]["AUTH"]["CD_SERVIDOR"] ){
				$dtUltimoAcesso = date('YmdHi');
				// O servidor foi definido
				// Registra o acesso como último acesso para o próximo acesso futuro
				$updateSessaoUsuario = new UsuarioAcessoModel();
				$updateSessaoUsuario->setCD_SERVIDOR( $codServidor );
				$updateSessaoUsuario->setCD_USUARIO( $user->getCD_USUARIO() );
				$updateSessaoUsuario->setDT_ULTIMO_ACESSO( $dtUltimoAcesso );
				//$usuarioacesso->setAudit(true);
				$updateSessaoUsuario->doUpdate('CD_SERVIDOR,CD_USUARIO');
				
				$sql = "update usuario set dt_ultimo_acesso =  $dtUltimoAcesso where cd_usuario = {$user->getCD_USUARIO()}";
				self::ExecSql($sql);
				
				$_SESSION[PROJECT_FOLDER]["AUTH"]["DICA"] = 1;				
			}
			//Fim alteração.			
		} else if ($user->getCD_GRUPO() == "61") {
			
			//CONSIGNATÁRIA
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNATARIA"] = $user->getCD_CONSIGNATARIA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_PERFIL"] = $user->getCD_PERFIL();
            $_SESSION[PROJECT_FOLDER]["AUTH"]["CD_TIPO_CONSIGNACAO"] = ConsignatariaController::getValueFieldNameCsg( $user->getCD_CONSIGNATARIA(), 'tipo_consignacao');
			
		} else if ($user->getCD_GRUPO() == "62") {
			
			//FILIAL CONSIGNATÁRIA			
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNATARIA"] = $user->getCD_CONSIGNATARIA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_FILIAL"]        = $user->getCD_FILIAL();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_PERFIL"] 		  = $user->getCD_PERFIL();
            $_SESSION[PROJECT_FOLDER]["AUTH"]["CD_TIPO_CONSIGNACAO"] = ConsignatariaFilialController::getValueFieldCsgf( $user->getCD_FILIAL() , 'cd_tipo_consignacao');

		} else if ($user->getCD_GRUPO() == "67") {
			
			// PDV
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNATARIA"] = $user->getCD_CONSIGNATARIA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_FILIAL"]        = $user->getCD_FILIAL();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_PDV"]			  = $user->getCD_PDV();

		} else if ($user->getCD_GRUPO() == "63") {
			
			//CORRESPONDENTE
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CORRESPONDENTE"]  = $user->getCD_CORRESPONDENTE();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"]           = $user->getCD_GRUPO();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_PERFIL"] 			= $user->getCD_PERFIL();

		} else if ($user->getCD_GRUPO() == "64") {

			//CONSIGNANTE
			$Consignante   = new ConsignanteController();
			$rsConsignante = $Consignante->getConsignanteByCod($user->getCD_CONSIGNANTE());

			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE_MASTER"] = $rsConsignante->getCD_CONSIGNANTE_MASTER();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE"]        = $rsConsignante->getCD_CONSIGNANTE();

		} else if ($user->getCD_GRUPO() == "65") {

			//AVERBADOR
			$Averbador     = new AverbadorController();
			$Consignante   = new ConsignanteController();
			$rsConsignante = $Consignante->getConsignanteByCod($user->getCD_CONSIGNANTE());
			$rsAverbador   = $Averbador->getAverbadorByCodUser($user->getCD_USUARIO());

			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE_MASTER"] = $rsConsignante->getCD_CONSIGNANTE_MASTER();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE"]        = $rsConsignante->getCD_CONSIGNANTE();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_AVERBADOR"]          = $rsAverbador->getCD_AVERBADOR();

		} else if ($user->getCD_GRUPO() == "66") {
			
			//CORRESPONDENTE FILIAL
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CORRESPONDENTE"]  = $user->getCD_CORRESPONDENTE();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"]           = $user->getCD_GRUPO();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_PERFIL"] 			= $user->getCD_PERFIL();
			
		}  else if ($user->getCD_GRUPO() == "68") {
			
			//AGENCIA
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNATARIA"]	= $user->getCD_CONSIGNATARIA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_FILIAL"]			= $user->getCD_FILIAL();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_AGENCIA"] 		= $user->getCD_AGENCIA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"]           = $user->getCD_GRUPO();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_PERFIL"] 			= $user->getCD_PERFIL();

		} else if ($user->getCD_GRUPO() == "69") {
			
			$agencia = new AgenciaFilialController();
			$AgenciaMaster = $agencia->getAgenciaFilial($user->getCD_AGENCIA());
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNATARIA"]	= $user->getCD_CONSIGNATARIA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_FILIAL"]			= $user->getCD_FILIAL();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_AGENCIA"]			= $AgenciaMaster->getCD_AGENCIA_MASTER();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_AGENCIA_FILIAL"]	= $user->getCD_AGENCIA();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"]           = $user->getCD_GRUPO();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_PERFIL"] 			= $user->getCD_PERFIL();
			
		}else if ($user->getCD_GRUPO() == "201") {

			$Averbador     = new AverbadorController();
			$Consignante   = new ConsignanteController();
			$rsConsignante = $Consignante->getConsignanteByCod($user->getCD_CONSIGNANTE());
			$rsAverbador   = $Averbador->getAverbadorByCodUser($user->getCD_USUARIO());

			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE_MASTER"] = $rsConsignante->getCD_CONSIGNANTE_MASTER();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_CONSIGNANTE"]        = $rsConsignante->getCD_CONSIGNANTE();
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_AVERBADOR"]          = $rsAverbador->getCD_AVERBADOR();
			
		}
		
		$_SESSION[PROJECT_FOLDER]["AUTH"]["ATUALIZACAO"] = 1;
		
		// Se for o primeiro acesso ATIVO_MUDA_SENHA fica setado automaticamente como 1 (TRUE)
		$mudarSenha = $user->getDT_ULTIMO_ACESSO();
		$mudarSenha = empty( $mudarSenha )  ? 1 : 0;		
		
		$dtAcessoAtual = date("YmdHis");
		$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_INICIO_ACESSO_ATUAL"] = $function->db2Date( $dtAcessoAtual , 'd/m/Y').' às '. $function->db2Date( $dtAcessoAtual ,'H:i:s');
		if ($user->getDT_ULTIMO_ACESSO()) {
			$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"] = $function->db2Date( $user->getDT_ULTIMO_ACESSO() , 'd/m/Y').' às '. $function->db2Date( $user->getDT_ULTIMO_ACESSO() ,'H:i:s');
		} else {
			$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"] = null;
		}
		
		$_SESSION[PROJECT_FOLDER]["AUTH"]["MUDAR_SENHA"]      = $user->getATIVO_MUDA_SENHA(); 
		// Recupera o acesso anterior como ultimo acesso 
		// Seta para o usuário trocar de senha quando:
		// - primeiro acesso
		// - O administrador marcou para o usuário trocar a senha
		//
		// Por enquanto somente o grupo servidor(48) é forçado a trocar de senha no primeiro logon e so passa a registrar 
		// a ultima sessão depois da troca de senha. Os menus também são desabilitados enquanto ele não trocar de senha.
		
		// Passou em todas verificações e gera uma nova ID de sessão e que não é a mesma ID de sessão quando entra no site 
		// session_regenerate_id(); - A função self::online é que gera a nova ID para registrar a ID_SESSION do usuário - carlos - 2017-11-10
		
		if(in_array($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"] , array(48) ) ) {
			if ( $user->getATIVO_MUDA_SENHA() == "0" ) {
				// Se marcado pelo administrador outras verificações podem não ser necessárias
				// 01 - Primeiro acesso, por exemplo			
				$_SESSION[PROJECT_FOLDER]["AUTH"]["MUDAR_SENHA"]      = $mudarSenha;
				// Atualizando o data de novo acesso como último acesso
				$userNewAcesso = new UsuarioModel();
				$userNewAcesso->setCD_USUARIO( $user->getCD_USUARIO() );
				if (!empty($_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"])){
					// Só passa a registrar o acesso do último login depois da troca de senha no primeiro logon
					$userNewAcesso->setDT_ULTIMO_ACESSO( $dtAcessoAtual );
				}		
				
				$userNewAcesso->setATIVO_MUDA_SENHA( $mudarSenha );
				//Ao entrar no sistema, caracteriza como Online;
				$userNewAcesso->setID_STATUS(1);
				$userNewAcesso->setID_SESSION(session_id());
				$userNewAcesso->setDT_ONLINE(date("YmdHis"));
				//Guardar o Ip do Usuário.
				$userNewAcesso->setDS_IP($_SERVER["REMOTE_ADDR"]);
				$userNewAcesso->setAudit(true);
				$userNewAcesso->doUpdate();
			}else{
				$userNewAcesso = new UsuarioModel();
				$userNewAcesso->setCD_USUARIO( $user->getCD_USUARIO() );
				$userNewAcesso->setID_STATUS(1);
				$userNewAcesso->setID_SESSION(session_id());
				$userNewAcesso->setDT_ONLINE(date("YmdHis"));
				//Guardar o Ip do Usuário.
				$userNewAcesso->setDS_IP($_SERVER["REMOTE_ADDR"]);
				$userNewAcesso->setAudit(true);
				$userNewAcesso->doUpdate();
			}
		} else {
			$userNewAcesso = new UsuarioModel();
			$userNewAcesso->setCD_USUARIO( $user->getCD_USUARIO() );
			$userNewAcesso->setDT_ULTIMO_ACESSO( $dtAcessoAtual );
			//Ao entrar no sistema, caracteriza como Online;
			$userNewAcesso->setID_STATUS(1);
			//Guardar o Ip do Usuário.
			$userNewAcesso->setDS_IP($_SERVER["REMOTE_ADDR"]);
			$userNewAcesso->setID_SESSION(session_id());
			$userNewAcesso->setDT_ONLINE(date("YmdHis"));
			$userNewAcesso->setAudit(true);
			$userNewAcesso->doUpdate();	
			// Se não tiver acesso anterior o acesso atual fica sendo com último acesso
			if( empty($_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"]) ) {
				$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"] = $function->db2Date( $dtAcessoAtual , 'd/m/Y').' às '. $function->db2Date( $dtAcessoAtual ,'H:i:s');
			}
		}
		
		$sessaoAudit = new AuditoriaController();
		$sessaoAudit->doInicioSessao();		
		//$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_SESSAO"] = $sessaoAudit->getCodSessaoByIdSessao();
		//$_SESSION[PROJECT_FOLDER]["AUTH"]["CD_SESSAO"] = session_id();
		// Quando se deseja registrar auditoria de banco de dados para determinado usuário				
		$_SESSION[PROJECT_FOLDER]["AUTH"]["AUDIT_SESSAO_USUARIO"] = false;
		
		$validacoesObrigatorias = ( ( in_array($user->getCD_GRUPO(),array(48)) && !$user->getDT_ULTIMO_ACESSO() ) || $user->getATIVO_MUDA_SENHA()) ? true : false;
		// Validacoes
		if($validacoesObrigatorias){
			// Ainda não será registrado o login por meio da função
			$_SESSION[PROJECT_FOLDER]["AUTH"]["VALIDACOES"] = true;
			$byPass = false;
		} else {
			//unset($_SESSION[PROJECT_FOLDER]["AUTH"]["VALIDACOES"]);
            $_SESSION[PROJECT_FOLDER]["AUTH"]["VALIDACOES"] = false;
		}
		
		// Todos os grupos passam por verificação de mensagens gerais e dicas na entrada
		// Na passagem pela HOME se tiver mensagem ou dica para o perfil, grupo ou geral é exibida  
		$_SESSION[PROJECT_FOLDER]["AUTH"]["MSG_GLOBAL_SYS"] = true;
	}
	
	/**
	 *
	 * Limpa valores da session de autenticação exceto as mensagens de erro que são retiradas da sessão em momento específicos
	 */
	private static function unsetSessionLogin(){
		AuditoriaController::doFimSessao();
		unset($_SESSION['captcha']);
		unset($_SESSION[PROJECT_FOLDER]['tentativasAcesso']);
		unset($_SESSION[PROJECT_FOLDER]["AUTH"]);
        unset($_SESSION[PROJECT_FOLDER]);
		unset($_SESSION);
		session_regenerate_id();
		//header("Cache-Control: no-cache, must-revalidate");
		//header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		
		// Destroi todas as variaveis inclusive as mensagens finais de sessão		
		//session_destroy();		
	}

	/**
	 * Verifica se o usuário esta autenticado caso não esteja redireciona para a tela de login. Redireciona para a tela de login com a mensagem de erro.
	 */
	public static function isAuth(){
		$Function = new CoreFunctions();  
		$a = intval($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_USUARIO"]);
		$b = intval($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"]);
		$c = ($_SESSION[PROJECT_FOLDER]["AUTH"]["NM_USUARIO"]);
		
		if(empty($a) || empty($b) || empty($c)){
			if(!$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"]){
				$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "Você precisa estar autenticado!";
			}
			$Function->doRedirect(CoreDefault::$DocumentUrl);
			exit;
			return false;
		}
		return true;
	}

	/**
	 *
	 * Retorna a lista de Usuários
	 * @param int $Limit | Número de registros por página
	 * @param int $Page  | Número da página
	 *
	 * @return array
	 */
	public function &getUsuarios($Limit = 20, $Page = 1,$ds_login= ""){
		$Usuario 	= new UsuarioModel();

		if(!empty($ds_login)){
			$Usuario->setDS_LOGIN()->doSortAsc();
			$Usuario->setDS_LOGIN($ds_login)->doLike();
		} else {
			$Usuario->setDS_LOGIN()->doSortAsc();
		}
		//$Usuario->setDebug(true);
		$Usuario->doFind($Limit,max(1,intval($Page)));
			
		return $Usuario;
	}
	public function &getUsuariosgrupo($Limit = 20, $Page = 1,$cd_grupo= ""){
		$Usuario 	= new UsuarioModel();
	
		if(!empty($cd_grupo)){
			$Usuario->setCD_GRUPO()->doSortAsc();
			$Usuario->setCD_GRUPO($cd_grupo);
		} else {
			$Usuario->setDS_LOGIN()->doSortAsc();
		}
		//$Usuario->setDebug(true);
		$Usuario->doFind($Limit,max(1,intval($Page)));
			
		return $Usuario;
	}	
	
	/**
	 * Decopõe a variável array $post, gera o sql e realiza a consulta
	 * @param array $post
	 * @param number $Page
	 * @param number $limit
	 * @param array $actionPage
	 * @return array
	 */
	public function getListUsuariosPage( $post ){
		
	    $actionPage = isset($post["actionPage"]) ? $post["actionPage"] : null;        
		$typeSql	= isset($post["typeSql"]) ? $post["typeSql"] : null;
		
		$fieldsSql = ",CASE
						        WHEN subQ_2.cd_grupo = 1 THEN -- Administrador
						          '[Infoconsig]'
						        WHEN subQ_2.cd_grupo = 48 THEN
					          	(
							            -- Logins como servidor que uma pessoa possa ter
										SELECT
											listagg('['||substr(v.nm_fantasia_cng,0,4)||'.' || substr(v.nm_fantasia_cng,11)||': '||v.nr_matricula|| ' - ' || v.ds_categoria_consignante ||']') within group( order by v.cd_servidor )
										FROM
											v_servidor v,
										    usuario_acesso ua
										WHERE
											v.cd_pessoa = subQ_2.cd_pessoa
										AND ua.cd_pessoa = v.cd_pessoa
										AND ua.cd_servidor = v.cd_servidor
										GROUP BY v.cd_pessoa

							           )
							        WHEN subQ_2.cd_grupo = 61 THEN -- Consignataria
							          ( SELECT '['||c.nm_fantasia||']' FROM consignataria c WHERE c.cd_consignataria= subQ_2.cd_consignataria)
							        WHEN subQ_2.cd_grupo = 62 THEN -- Filial Consignataria
							          ( SELECT '['||c.nm_filial||']' FROM consignataria_filial c WHERE c.cd_filial = subQ_2.cd_filial)
							        WHEN subQ_2.cd_grupo = 63 THEN -- Correspondente
							          ( SELECT '['||c.nm_correspondente||']' FROM correspondente c WHERE c.cd_correspondente= subQ_2.cd_correspondente)
							        WHEN subQ_2.cd_grupo = 64 THEN -- Consignante
							          ( SELECT '['||c.nm_fantasia||']' FROM consignante c WHERE c.cd_consignante= subQ_2.cd_consignante)
							        WHEN subQ_2.cd_grupo = 65 THEN -- Averbador
							          ( SELECT '['||a.nm_averbador||']' FROM averbador a WHERE a.cd_averbador= subQ_2.cd_averbador )
							        WHEN subQ_2.cd_grupo = 66 THEN -- Filial Correspondente
							          ( SELECT '['||c.nm_correspondente||']' FROM correspondente c WHERE c.cd_correspondente= subQ_2.cd_correspondente)
							        WHEN subQ_2.cd_grupo = 67 THEN -- PDV
							          --( SELECT '['||p.nm_pdv||']' FROM pdv p where p.cd_consignataria = subQ_2.cd_consignataria AND p.cd_filial_consignataria = subQ_2.cd_filial  AND p.cd_pdv = subQ_2.cd_pdv )
									  ( SELECT '['|| c.nm_fantasia ||' / '||p.nm_pdv||']' FROM pdv p, consignataria_filial c where p.cd_consignataria = subQ_2.cd_consignataria AND p.cd_filial_consignataria = subQ_2.cd_filial  AND p.cd_pdv = subQ_2.cd_pdv  AND c.cd_filial = p.cd_filial_consignataria AND c.cd_consignataria = p.cd_consignataria )
							        WHEN subQ_2.cd_grupo = 68 THEN -- Agencia
							          (SELECT '['||a.nm_agencia||']' FROM agencia a WHERE a.cd_agencia = subQ_2.cd_agencia)
							        WHEN subQ_2.cd_grupo = 69 THEN -- Agencia Filial
							          (SELECT '['||a.nm_agencia||']' FROM agencia a WHERE a.cd_agencia = subQ_2.cd_agencia)
							        WHEN subQ_2.cd_grupo = 201 THEN -- Servfacil
							          '[]'
							        ELSE
							          '[]'
							    END  ds_local
							    ,
				              	(
				              	SELECT 
				                  CASE
						            WHEN subQ_2.cd_grupo = 1 THEN -- Administrador
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 48 THEN
						              '['||uspg.nm_grupo||']'
						            WHEN subQ_2.cd_grupo = 61 THEN -- Consignataria
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 62 THEN -- Filial Consignataria
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 63 THEN -- Correspondente
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 64 THEN -- Consignante
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 65 THEN -- Averbador
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 66 THEN -- Filial Correspondente
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 67 THEN -- PDV
						              '['||uspg.nm_grupo||']'
						            WHEN subQ_2.cd_grupo = 68 THEN -- Agencia
						              '['||uspg.nm_grupo||']'||nvl( (select '['||nm_permissao||']' from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and subQ_2.cd_perfil = vp.id_perfil ),'') 
						            WHEN subQ_2.cd_grupo = 69 THEN -- Agencia Filial
						              '['||uspg.nm_grupo||']'
						            WHEN subQ_2.cd_grupo = 201 THEN -- Servfacil
						              '['||uspg.nm_grupo||']'
						            ELSE
						              '['||uspg.nm_grupo||']'
						        END 
			              FROM usuario_permissao_grupo uspg WHERE uspg.cd_grupo = subQ_2.cd_grupo )
			              DS_GRUPO_PERFIL";
		
        if( in_array( $typeSql, array( "PDF") ) ){
			// Sem paginação
			$sql = $this->getParserSqlUsuarios( $post ); 
			$sql = "SELECT
						subQ_2.*
						$fieldsSql	
					FROM
					(	
						SELECT
							ROWNUM rnum,
							subQ.*
						FROM 
						(
							$sql
						) subQ							
					) subQ_2";	
					
					
		} else {
		    $where_paginacao = "";
		    
			// Com paginação
			$limit	= isset($actionPage[ 2 ]) ? $actionPage[2] : 10;
			
			if( $typeSql == "totalByPage"){
				
				$post["totalPorPagina"] = $limit;
				$sql 	= $this->getParserSqlUsuarios( $post , "totalByPage" );
				
			} else {
				             
				$Page 	= (isset($actionPage[1])) ? $actionPage[1] : 1;
				$sql 	= $this->getParserSqlUsuarios( $post );
				
				if( $Page && $limit ) {
					if( !$limit ){
						$limit = 10;
					}
					$where_paginacao = " WHERE rnum > $limit * ($Page - 1) AND rnum <= $limit * $Page ";
				}
				
				$sql = "SELECT
							subQ_2.*
							$fieldsSql	
						FROM
						(	
							SELECT
								ROWNUM rnum,
								subQ.*
							FROM 
							(
								$sql
							) subQ							
						) subQ_2
						$where_paginacao";				
			}
		}
        
		//debugVar("--line ".__LINE__." \n $sql ;\n",0, array(1,__CLASS__.'--'.__FUNCTION__));
		$rs  = self::ExecSql($sql);
		
		return $rs;
	}
	
	/**
	 * 
	 * @param array $post
	 * @param string $tipoSql
	 */
	private function getParserSqlUsuarios( $post , $tipoSql = null){
		
		$varPosts = "totalPorPagina,codGrupo,codItemGrupo,codPermissao,dsTexto,dsStatus,fieldOrderBy";
		$varPosts = explode(",",$varPosts);
		foreach ($varPosts as $var){
			${"$var"} = isset($post[ $var ]) ? $post[ $var ] : null;
		}
		
        $where_ds_login	= "";
		$where_pessoa	= "";
		$where_grupo	= "";
		$where_status	= "";
		$where_item		= "";
		$where_perfil	= "";
		$orderBy		= "";
		
		$sessaoLogOn    = $_SESSION[PROJECT_FOLDER]["AUTH"];
		$codGrupoSessao = $sessaoLogOn["CD_GRUPO"];
		
		if( $fieldOrderBy == "DS_LOGIN"){
			$orderBy = " ORDER BY Nls_Upper( us.ds_login ) , Nls_Upper( pes.nm_pessoa)";
		}
		
		if( $fieldOrderBy == "PESSOA"){
			$orderBy = " ORDER BY Nls_Upper( pes.nm_pessoa) , Nls_Upper( us.ds_login ) ";
		}
		
		if(isset($dsTexto["user"])){
			$where_ds_login = "[nls_upper( us.ds_login) like nls_upper('%".$dsTexto["user"]."%')]";
		}
		if(isset($dsTexto["pes"])){
			$where_pessoa = "[nls_upper( pes.nm_pessoa) like nls_upper('%".$dsTexto["pes"]."%')]";
		}
		
		if( $dsStatus == "Online"){
			$where_status = "[us.ID_STATUS = 1]";
		}elseif( $dsStatus == "Offline"){
			$where_status = "[us.ID_STATUS in ( 0 , 2)]";
		}
		
		if($codPermissao){
			$where_perfil = "[ exists ( select vp.* from vinculo_perfil vp where vp.id_perfil = us.cd_perfil AND vp.cd_permissao = $codPermissao)]";
		}
		
		//Verifica se quem esta consultando o cadastro de usuario pertence a algum desses grupo para vizualizar somente do mesmo grupo.
		$arrayGrupo = array(61,62,63,66,67,68,69,65,64);
        $where_padrao_sessao = "";
		if (in_array($codGrupoSessao, $arrayGrupo)){
			if ($codGrupoSessao == 61){
			    $where_padrao_sessao = "us.cd_consignataria = ".$sessaoLogOn['CD_CONSIGNATARIA'];
			}elseif ($codGrupoSessao == 62){
				$where_padrao_sessao = "us.cd_filial = ".$sessaoLogOn['CD_FILIAL'];
			}elseif ($codGrupoSessao == 63){
				$_codCorrespondente = $sessaoLogOn['CD_CORRESPONDENTE'];
				$where_padrao_sessao = "EXISTS (
			                        SELECT subc.cd_correspondente FROM (  
			                          SELECT cc.cd_correspondente  FROM  correspondente cc WHERE cc.cd_correspondente = $_codCorrespondente
			                            UNION 
			                          SELECT cc.cd_correspondente  FROM  correspondente cc WHERE cc.cd_correspondente_master = $_codCorrespondente
			                        ) subc 
			                        WHERE 
			                          subc.cd_correspondente = us.cd_correspondente
			                        )";
				if($codGrupo!=63){
					// Sessão master e busca pelo filial
					$where_padrao_sessao .= " and exists (SELECT cc.cd_correspondente  FROM  correspondente cc WHERE cc.cd_correspondente_master = $_codCorrespondente ) ";
				}
			}elseif($codGrupoSessao == 66) {
				$where_padrao_sessao = "us.cd_correspondente = ".$sessaoLogOn['CD_CORRESPONDENTE'];
			}elseif($codGrupoSessao == 67) {
				$where_padrao_sessao = "us.cd_consignataria = ".$sessaoLogOn['CD_CONSIGNATARIA'];
			}elseif($codGrupoSessao == 68){
				$where_padrao_sessao = "us.cd_agencia = ".$sessaoLogOn['CD_AGENCIA'];
			}elseif($codGrupoSessao == 69){
				$where_padrao_sessao = "us.cd_agencia = ".$sessaoLogOn['CD_AGENCIA_FILIAL'];
			}elseif($codGrupoSessao == 64){
				$where_padrao_sessao = "us.cd_consignante = ".$sessaoLogOn['CD_CONSIGNANTE'];
			}elseif($codGrupoSessao == 65){
				$where_padrao_sessao = "us.cd_averbador = ".$sessaoLogOn['CD_AVERBADOR'];
			}
		} 
		
		/*
		// A partir do formulário
		//1  - Administrador          
		//48 - Servidor 			 - escolhe consignante              
		//61 - Consignataria         - escolhe consignataria 
		//62 - Filial Consignataria  - escolhe filial consignataria
		//63 - Correspondente        - escolhe correspondente master 
		//64 - Consignante 			 - escolhe consignante           
		//65 - Averbador             - escolhe averbador 
		//66 - Filial Correspondente - escolhe filial correspondente 
		//201 - Serv-facil           - escolhe consignataria 
		//67 - PDV                   - escolhe consignataria
		//68 - Agência Master		 - escolhe Agência master
		//69 - Agência Filial		 - escolhe Agência Filial
		*/		
		switch ($codGrupo){
			case 1: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				break;
			case 48: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				// Login de usuário servidor visualiza todas as suas matrículas em vários consignantes se houver 
				if(!empty($codItemGrupo)){
					$where_item2 = "and ua.cd_consignante = $codItemGrupo";
					$where_item = "[us.cd_usuario IN ( SELECT distinct ua2.cd_usuario FROM usuario_acesso ua2 WHERE ua2.cd_consignante = $codItemGrupo )]";
				}
				break;
			case 61: 
				if(!empty($codItemGrupo)){
					if(in_array( $codGrupoSessao, array(63,66))){
					    // Para correspondentes cadastradas por consignatárias
						$where_item = "[ us.cd_correspondente in ( select cor.cd_correspondente from correspondente cor where cor.cd_consignataria_insert = ".$codItemGrupo." ) ]"; 
					}else{
						$where_item = "[ (us.cd_consignataria = $codItemGrupo  or us.cd_correspondente in ( select cor.cd_correspondente from correspondente cor where cor.cd_consignataria_insert = $codItemGrupo ) ) ]";
					}
					if ( $codGrupo ) {
						$where_grupo = "[us.cd_grupo = $codGrupo ]";
					} 
				}else{
					$where_grupo = "[us.cd_grupo = $codGrupo]";
				}
				break;
			case 62: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_filial = $codItemGrupo]";
				}
				break;
			case 63: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_correspondente = $codItemGrupo]";
				}
				break;
			case 64: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_consignante = $codItemGrupo]";
				}
				break;
			case 65: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_averbador = $codItemGrupo]";
				}
				break;
			case 66: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_correspondente = $codItemGrupo]";
				}
				break;
			case 67: 
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_consignataria = $codItemGrupo]";
				}
				break;
			case 68:
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_agencia = $codItemGrupo]";
				}
				break;
			case 69:
				$where_grupo = "[us.cd_grupo = $codGrupo]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_agencia = $codItemGrupo]";
				}
				break;
			case 201: 
				$where_grupo = "[us.cd_grupo = $codGrupo ]";
				if(!empty($codItemGrupo)){
					$where_item = "[us.cd_consignante = $codItemGrupo]";
				}
				break;
			default:
                null;			
		}
		
		if($where_padrao_sessao){
		    $where_item .= "[$where_padrao_sessao]";
		}
		
		$where_sql = $where_ds_login.
					 $where_pessoa.
					 $where_grupo.
					 $where_status.
					 $where_item.
					 $where_perfil;
		$where_sql = str_replace(array("][","[","]"),array(" and ","",""), $where_sql);
		$where_sql = (empty($where_sql)) ? "" : $where_sql." and ";
		
		switch ($tipoSql) {
			case "totalByPage":
			    if(0==$totalPorPagina){
			        // Uma única pagina com todos os registros 
			        $totalPorPagina = "Count(*)";  
			    }
				$sql = "SELECT
							$totalPorPagina LIMIT_RECORDS, Round(Count(*) / $totalPorPagina + 0.49) total_pages
						FROM
							usuario us,
						    pessoa pes
						WHERE
							$where_sql
							pes.cd_pessoa = us.cd_pessoa";
				break;
			case "totalGeral":
				$sql = "SELECT
						    Count(*) qtd_geral
						    ,ID_STATUS
						    ,case ID_STATUS when 1 then 'Online' else 'Offline' end DS_STATUS
						    ,case ID_STATUS when 1 then '<span class=\"user_on\"> </span>' else '<span class=\"user_off\"> </span>' end status_figura
						FROM (
							SELECT 
						    	--CASE WHEN us.id_status IN( 0 , 2 ) THEN 0 ELSE 1 END ID_STATUS  
						    	CASE WHEN us.id_status = 1 THEN 1 ELSE 0 END ID_STATUS
							FROM 
						    	usuario us,
								pessoa pes
							WHERE
								$where_sql
								pes.cd_pessoa = us.cd_pessoa 
						) 
						group by ID_STATUS";
				break;	
			default:
				$sql = "SELECT
							us.cd_usuario,
							us.ds_login USUARIO_DS_LOGIN,
							pes.nm_pessoa USUARIO_PESSOA,
							us.cd_grupo,
							us.ds_login,
							us.ds_email,
							us.id_ativo,
							us.ds_senha,
							us.dt_insert,
							us.dt_update,
							us.cd_usuario_insert,
							us.cd_usuario_update,
							us.cd_consignataria,
							us.cd_filial,
							us.cd_pdv,
							us.cd_agencia,
							us.cd_correspondente,
							us.cd_consignante_master,
							us.cd_consignante,
							us.cd_averbador,
							us.cd_servidor,
							us.cd_pessoa,
							us.dt_ultimo_acesso,
							us.ativo_muda_senha,
							us.cd_perfil,
					    	nvl( (select nm_permissao from usuario_permissao up, vinculo_perfil vp where up.cd_permissao = vp.cd_permissao and us.cd_perfil = vp.id_perfil ),'') nm_perfil,
					    	pes.nm_pessoa,					    	
					    	us.ID_STATUS,
					    	US.DS_IP,
					    	us.ds_ultima_acao,
							pes.nr_cpf						    	
						FROM
							usuario us,
						    pessoa pes
						WHERE
							$where_sql
							pes.cd_pessoa = us.cd_pessoa
						$orderBy";
				break; 	
		}
		//debugVar("--line ".__LINE__." $tipoSql \n$sql\n;", 0, array(1,__CLASS__.'--'.__FUNCTION__));
		return $sql;
	}
	
	/**
	 *
	 * Retorna link para deletar o Grupos de Permissões
	 * @param string $Qs | querystring amigavel ex: "cod/1"
	 * @param int $CodGrupo | Código do Grupo para verificar se o Grupos de Permissões esta em uso
	 *
	 * @return string
	 */
	public function getLinkDelete($Qs,$CodUsuario){
		$Html 				= new AdmCmsHtmlController();
		$Grupo				= new UsuarioPermissaoGrupoModel();
		$Grupo->setCD_USUARIO_INSERT(intval($CodUsuario));
		if($Grupo->getCount())
		$Enable = false;
		else
		$Enable = true;
			
		return $Html->getLinkDelete($Qs, $Enable);
	}

	/**
	 * Cadastra novo usuário
	 * @param array $post | Post vindo do formulário
	 */
	public function doInsertUsuario($post){
	    
	    $codUsuario   = null;
		$msg          = "";		
		$codGrupo     = $post["CD_GRUPO"];
		
		if($codGrupo){
		    
		    $codPessoa = $post['CD_PESSOA'];
		    
			$sessao = $_SESSION[PROJECT_FOLDER]["AUTH"];
			
			$Usuario  = new UsuarioModel();
			$Security = new CoreWebSecurity();
			$format   = new formataCampoController();
						
			$Usuario->doClearFields();
			
			$Usuario->setCD_PESSOA(intval( $codPessoa ));
			$Usuario->setCD_GRUPO( $codGrupo );
			$Usuario->setDS_LOGIN(trim($post["DS_LOGIN"]));
	
			//limpa todos os codigos externos atribuidos ao usuario
			$Usuario->setCD_AVERBADOR(null);
			$Usuario->setCD_CONSIGNANTE_MASTER(null);
			$Usuario->setCD_CONSIGNANTE(null);
			$Usuario->setCD_CONSIGNATARIA(null);
			$Usuario->setCD_FILIAL(null);
			$Usuario->setCD_PDV(null);
							
			if($codGrupo == 65){ //averbador
				$Usuario->setCD_AVERBADOR($post["CD_AVERBADOR"]);
				$Usuario->setCD_CONSIGNANTE_MASTER($post["CD_CONSIGNANTE_MASTER"]);
				$Usuario->setCD_CONSIGNANTE($post["CD_CONSIGNANTE"]);
			}else if ($codGrupo == 61) { //consignatária
				$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA"]);
				//deixa nulo os campos desnecessários na gravação referente a esta regra.
			}else if ($codGrupo == 62) { //filial consignataria
				$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA"]);
				$Usuario->setCD_FILIAL($post['CD_FILIAL']);
			}else if ($codGrupo == 67) { //PDV
				$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA"]);
				$Usuario->setCD_FILIAL($post['CD_FILIAL']);
				$Usuario->setCD_PDV($post["CD_PDV"]);
			}else if ($codGrupo == 63) { //correspondente
				$Usuario->setCD_CORRESPONDENTE($post["CD_CORRESPONDENTE_MASTER"]);
			}else if ($codGrupo == 66) { //correspondente
				$Usuario->setCD_CORRESPONDENTE($post["CD_CORRESPONDENTE_FILIAL"]);
			}else if ($codGrupo == 64) { //consignante
				$Usuario->setCD_CONSIGNANTE_MASTER($post["CD_CONSIGNANTE_MASTER"]);
				$Usuario->setCD_CONSIGNANTE($post["CD_CONSIGNANTE"]);
			}else if ($codGrupo == 48) { //servidor
				if ($post["ATIVO_MUDA_SENHA"]){
					$Usuario->setATIVO_MUDA_SENHA($post["ATIVO_MUDA_SENHA"]);
				}
			}else if ($codGrupo == 201){ //Ativação de Cartão Servfácil
				
				$Usuario->setCD_AVERBADOR($post["CD_AVERBADOR"]);
				$Usuario->setCD_CONSIGNANTE_MASTER($post["CD_CONSIGNANTE_MASTER"]);
				$Usuario->setCD_CONSIGNANTE($post["CD_CONSIGNANTE"]);
				
			}else if ($codGrupo == 68){ //Agência
				
				$codGrupoSessao = $sessao["CD_GRUPO"];				
				if(in_array($codGrupoSessao,array(61,62,68))){
					
					$post["CD_CONSIGNATARIA"]	= ($post["CD_CONSIGNATARIA"]) ? $post["CD_CONSIGNATARIA"] : $sessao["CD_CONSIGNATARIA"];
					$post["CD_FILIAL"]			= ($post["CD_FILIAL"]) ? $post["CD_FILIAL"] : $sessao["CD_FILIAL"];
					$post["CD_AGENCIA"]			= ($post["CD_AGENCIA"]) ? $post["CD_AGENCIA"] : $sessao["CD_AGENCIA"];
					
				} else {					
					
					$codAgencia = $post["CD_AGENCIA"];
					if($codAgencia){
						$codCsg = AgenciaMasterController::getValueField( $post["CD_AGENCIA"] , "cd_consignataria");
						if(!$post["CD_CONSIGNATARIA"]){
							$post["CD_CONSIGNATARIA"] = $codCsg;
						}elseif($post["CD_CONSIGNATARIA"] != $codCsg){
							$msg	.= "[- Consignatária inválida para esta agência]";
						}
						$codCsgf = AgenciaMasterController::getValueField( $post["CD_AGENCIA"] , "cd_filial");
						if(!$post["CD_FILIAL"]){
							$post["CD_FILIAL"] = $codCsgf;
						}elseif($post["CD_FILIAL"] != $codCsgf){
							$msg	.= "[- Consignatária Filial inválida para esta agência]";
						}
					} else {
						$msg .= "[- Agência não informada para esse cadastro de usuário]"; 
					}
				}
				
				$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA"]);
				$Usuario->setCD_FILIAL($post["CD_FILIAL"]);
				$Usuario->setCD_AGENCIA($post["CD_AGENCIA"]);
				
			}else if ($codGrupo == 69){ //Agência Filial
				
				$codGrupoSessao = $sessao["CD_GRUPO"];
				if(in_array($codGrupoSessao,array(61,62,68,69))){
						
					$post["CD_CONSIGNATARIA"]	= ($post["CD_CONSIGNATARIA"]) ? $post["CD_CONSIGNATARIA"] : $sessao["CD_CONSIGNATARIA"];
					$post["CD_FILIAL"]			= ($post["CD_FILIAL"]) ? $post["CD_FILIAL"] : $sessao["CD_FILIAL"];
					$post["CD_AGENCIA"]			= ($post["CD_AGENCIA"]) ? $post["CD_AGENCIA"] : $sessao["CD_AGENCIA"];
						
				} else {
					
					$codAgencia = $post["CD_AGENCIA"];
					if($codAgencia){
						$codCsg = AgenciaMasterController::getValueField( $post["CD_AGENCIA"] , "cd_consignataria");
						if(!$post["CD_CONSIGNATARIA"]){
							$post["CD_CONSIGNATARIA"] = $codCsg;
						}elseif($post["CD_CONSIGNATARIA"] != $codCsg){
							$msg	.= "[- Consignatária inválida para esta agência]";
						}
						$codCsgf = AgenciaMasterController::getValueField( $post["CD_AGENCIA"] , "cd_filial");
						if(!$post["CD_FILIAL"]){
							$post["CD_FILIAL"] = $codCsgf;
						}elseif($post["CD_FILIAL"] != $codCsgf){
							$msg	.= "[- Consignatária Filial inválida para esta agência]";
						}
					} else {
						$msg .= "[- Agência não informada para esse cadastro de usuário]";
					}
				}
				
				$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA"]);
				$Usuario->setCD_FILIAL($post["CD_FILIAL"]);
				$Usuario->setCD_AGENCIA($post["CD_AGENCIA_FILIAL"]);
			}
			
			$Usuario->setCD_PERFIL( (isset( $post["CD_PERFIL"] ) ? $post["CD_PERFIL"] : null ) );
			//$Usuario->setDS_EMAIL($post['DS_EMAIL']);
			
			if(!$msg){
				
			    if($post['DS_SENHA'] != '*********')
				    $Usuario->setDS_SENHA($Security->doMd5($post['DS_SENHA']));
			    
				$Usuario->setID_ATIVO(max(0,$post['ID_ATIVO'])."");
				$Usuario->setDT_INSERT(date("Ymdhi"));
				$Usuario->setCD_USUARIO_INSERT($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_USUARIO"]);
				
				if ($format->senhaValida($Usuario->getDS_SENHA())) {
				    
				    $Usuario->setDS_SENHA($Security->doMd5($post['DS_SENHA']));
					
				    try{
				        
				        $codUsuario = $Usuario->getMax("CD_USUARIO")+1;
				        $Usuario->setCD_USUARIO( $codUsuario );				        
				        
				        $Usuario->setAudit(true);
    					//$Usuario->setDebug(true);
    					$Usuario->doInsert();				        
    					$success = true;
    					
				    }catch (Exception $e) {
				        writeTxtFile("; ". date("d/m/Y H:i:s"). " ; ".get_class($this)." ;\"".doCleanCommentsSql( ( is_array($e->sql) ? $e->sql[0] : $e->sql)  )."\";". preg_replace("/[\s]+/", " ", $e->msg) .";\n" , CoreDefault::$DocumentRoot.__CLASS__.'-'.__FUNCTION__);
				        $success = false;
				    }
				    
				    if( $success ) {				        
				        if( isset( $post['EnvEmail'] ) && $post['EnvEmail'] == 1 ){
				        
        					//busca descricao email com o codigo de pessoa
        					$sql = "select p.DS_EMAIL from pessoa p where p.CD_PESSOA = $codPessoa";
        					$rs  = self::ExecSql($sql);
        					
        					$destinatario = $rs->fields['DS_EMAIL'];
        					
        					if($destinatario){
        					    
            					$destinatarioArray[]   = $destinatario;
            					
        						$gerenciaEmail = new GerenciaEmailController();
        						$gerenciaEmail->doEnviarEmailRecuperacaoUsuarioSenha($post['DS_LOGIN'], $post['DS_SENHA'], $destinatarioArray);        					    
        					} else {
        					    $msg = "[- O usuário não tem email cadastrado.]";
        					}
    					}				        
				    }					
				} else {
				    $success = false;
					$msg = "[- Senha inválida.]";
				}
			}
		}else{
			// nada feito
			$success = false;
		}
		$arrayReturn["CD_USUARIO"]    = ($success) ? $codUsuario : null; // Retorna se tiver sucesso
		$arrayReturn["msg"]           = $msg;
		$arrayReturn["success"]       = $success;
		return $arrayReturn;	
	}

	/**
	 * Atualiza dados do usuario e envia e-mail com a senha se estiver definido para isso
	 * @param array $post | Post vindo do formulário
	 */
	public static function doUpDateUsuario($post){
		
		$Usuario 	= new UsuarioModel();
		$Security 	= new CoreWebSecurity();
		
		$Usuario->setCD_USUARIO( $post['CD_USUARIO'] );
		
		// Todos os acessos tem uma pessoa vinculada e demais grupos conforme atores no sistema
		// A pessoa não pode ser mudada depois de criada para determinado usuário
		//$Usuario->setCD_PESSOA( $post['CD_PESSOA'] );
		
		$Usuario->setCD_GRUPO(intval($post['CD_GRUPO']));
		
		$codGrupoSession	= $_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"];
		$codGrupoUpdate		= $post['CD_GRUPO']; // Grupo alvo do usuário sendo alterado
		
		$alteraLogin = false;
		switch ($codGrupoSession){
		    case 1:
		        // 1 - Administrador
		        $alteraLogin = in_array( $codGrupoUpdate, array(1,48,64,65,61,62,63,64,65,66,67,68,69,201) );
				if(48==$codGrupoUpdate && isset($post['AtivacaoServfacil']) && $post['AtivacaoServfacil']==true ){
					$alteraLogin = true;
				}
		        break;
		    case 48:
		        // 48 - Servidor
		        break;
		    case 61:
		        // 61 - Consignatária
		        $alteraLogin = in_array( $codGrupoUpdate, array(61,62,67,68,69) );
		        break;
		    case 62:
		        // 62 - Filial Consignatária
		        $alteraLogin = in_array( $codGrupoUpdate, array(62,67,68,69) );
		        break;
		    case 63:
		        // 63 - Correspondente
		        $alteraLogin = in_array( $codGrupoUpdate, array(63,66) );
		        break;
		    case 64:
		        // 64 - Consignante
		        $alteraLogin = in_array( $codGrupoUpdate, array(64,65) );
		        break;
		    case 65:
		        // 65 - Averbador
		        $alteraLogin = in_array( $codGrupoUpdate, array(65) );
		        break;
		    case 66:
		        // 66 - Filial Correspondente
		        $alteraLogin = in_array( $codGrupoUpdate, array(66) );
		        break;
		    case 67:
		        // 67 - PDV
		        $alteraLogin = in_array( $codGrupoUpdate, array(67) );
		        break;
		    case 68:
		        // 68 - Agência
		        $alteraLogin = in_array( $codGrupoUpdate, array(68,69) );
		        break;
		    case 69:
		        // 69 - Agência Filial
		        $alteraLogin = in_array( $codGrupoUpdate, array(69) );
		        break;
		    case 201:
		        // 201 - Ativação Servfacil
		        $alteraLogin = in_array( $codGrupoUpdate, array(201) );
		        break;
		}
		
		if($alteraLogin && isset($post["DS_LOGIN"]) && $post["DS_LOGIN"] ){
		    $Usuario->setDS_LOGIN($post["DS_LOGIN"]);			
		}

		//limpa todos os codigos externos atribuidos ao usuario
		$Usuario->setCD_CONSIGNANTE_MASTER(null);
		$Usuario->setCD_CONSIGNANTE(null);
		$Usuario->setCD_AVERBADOR(null);
		$Usuario->setCD_CONSIGNATARIA(null);
		$Usuario->setCD_FILIAL(null);
		$Usuario->setCD_PESSOA(null);
		$Usuario->setCD_SERVIDOR(null);
		$Usuario->setCD_CORRESPONDENTE(null);
		
		// e seta novamente
		if ($Usuario->getCD_GRUPO() == 65){ //averbador
			$Usuario->setCD_CONSIGNANTE_MASTER($post["CD_CONSIGNANTE_MASTER"]);
			$Usuario->setCD_CONSIGNANTE($post["CD_CONSIGNANTE"]);
			$Usuario->setCD_AVERBADOR($post["CD_AVERBADOR"]);

		} else if ($Usuario->getCD_GRUPO() == 1) { //Administrador

		} else if ($Usuario->getCD_GRUPO() == 61) { //consignatária
			$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA"]);

		} else if ($Usuario->getCD_GRUPO() == 62) { //filial consignataria
			$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA_EDIT"]);
			$Usuario->setCD_FILIAL($post["CD_FILIAL"]);

		} else if ($Usuario->getCD_GRUPO() == 67) { //filial consignataria
			$Usuario->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA_EDIT"]);
			$Usuario->setCD_FILIAL($post["CD_FILIAL"]);

		} else if ($Usuario->getCD_GRUPO() == 63) { //correspondente master
			$Usuario->setCD_CORRESPONDENTE($post["CD_CORRESPONDENTE_MASTER"]);

		} else if ($Usuario->getCD_GRUPO() == 66) { //correspondente filial
			$Usuario->setCD_CORRESPONDENTE($post["CD_CORRESPONDENTE_FILIAL"]);

		} else if ($Usuario->getCD_GRUPO() == 64) { //consignante
			$Usuario->setCD_CONSIGNANTE_MASTER($post["CD_CONSIGNANTE_MASTER"]);
			$Usuario->setCD_CONSIGNANTE($post["CD_CONSIGNANTE"]);
				
		} else if ($Usuario->getCD_GRUPO() == 48) { //servidor
			//	$Usuario->setCD_CONSIGNANTE_MASTER($post["CD_CONSIGNANTE_MASTER"]);
			//	$Usuario->setCD_CONSIGNANTE($post["CD_CONSIGNANTE"]);
			//	$Usuario->setCD_AVERBADOR($post["CD_AVERBADOR"]);
			//	$Usuario->setCD_SERVIDOR($post["CD_SERVIDOR"]);
		}
			
		$Usuario->setCD_PERFIL( (isset($post["CD_PERFIL"]) ? $post["CD_PERFIL"] : null ) );
		//$Usuario->setDS_EMAIL($post['DS_EMAIL']);
		$Usuario->setID_ATIVO(max(0,$post['ID_ATIVO'])."");
		
		$Usuario->setDT_UPDATE(date("Ymdhi"));
		$Usuario->setCD_USUARIO_UPDATE($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_USUARIO"]);
		// Se o usuário estava marcado para mudar senha então desmarca
		$Usuario->setATIVO_MUDA_SENHA(0);
		//$senhaAtualizada = false;
		if (isset($post["DT_ULTIMO_ACESSO"])){
			$Usuario->setDT_ULTIMO_ACESSO( $post['DT_ULTIMO_ACESSO'] );	
		}
		
		$atualiza = false;
		
		if( isset($post["DS_SENHA"]) && $post["DS_SENHA"] != "" && empty($post["DS_SENHA_CONF"]) ){
			//Senha preenchida e Confirmação não
			$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "Favor digitar a confirmação da senha.";
		}else if ( !empty($post["DS_SENHA"]) && !empty($post["DS_SENHA_CONF"]) && ($post["DS_SENHA"] == $post["DS_SENHA_CONF"]) ){
			$atualiza = true;
			$Usuario->setDS_SENHA(md5($post["DS_SENHA"]));
			$Usuario->setDS_SENHA($Security->doMd5($post['DS_SENHA']));			
		}else if (empty($post["DS_SENHA"]) && empty($post["DS_SENHA_CONF"])  ){
			$atualiza = true;
		}else if ($post["DS_SENHA"] != $post["DS_SENHA_CONF"]){
			$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "As senhas digitadas não conferem.";
		}
		
		if ($atualiza){
			$Usuario->setAudit(true);
			//$Usuario->setDebug(true);
			$Usuario->doUpdate();
			
			if( isset($post['EnvEmail']) && $post['EnvEmail'] == 1 ){
			    
				$sql = "select p.DS_EMAIL from pessoa p where p.CD_PESSOA = (SELECT u.cd_pessoa FROM usuario u WHERE u.cd_usuario = {$post['CD_USUARIO']})";
				
				$rs  = self::ExecSql($sql);
    			$destinatario  = $rs->fields['DS_EMAIL'];			
    			
			    if ( $destinatario ){
			        $destinatarioArray[] = $destinatario;
			        $gerenciaEmail = new GerenciaEmailController();
			        $gerenciaEmail->doEnviarEmailRecuperacaoUsuarioSenha($post['DS_LOGIN'], $post['DS_SENHA'], $destinatarioArray);			        
			    }
			}
			
			return true;
		}
		
		return false;
	}
		
	/**
	 * Verifica se o usuário da sessão tem permissão de acesso determinados menus e retorna true ou false 
	 * @param number $menu
	 * @return boolean
	 */
	public function temPermissao($menu){
		if (!$menu) { 
			//quando menu nao estiver atribuido para ter permissão, por exemplo: pagina inicial
			return true;
		}
		
		$codPerfil		= $_SESSION[PROJECT_FOLDER]["AUTH"]['CD_PERFIL']; 
				
		if($codPerfil){
			//Verificação de permissão por PERFIL se houver perfil na sessão
			$Perfil	= new VinculoPerfilController();
			return 	$Perfil->getPermissaoMenuByUsuarioMenu($_SESSION[PROJECT_FOLDER]["AUTH"]['CD_USUARIO'], $menu);
		} else {
			$grupoSessaoPerm 	= $_SESSION[PROJECT_FOLDER]["AUTH"]['CD_GRUPO'];
			$_sql 	= "SELECT count(*) FROM USUARIO_PERMISSAO_GRUPO_ITEM WHERE CD_GRUPO = ".$grupoSessaoPerm." AND CD_MENU = ".$menu." and id_ativo = 1 "; 
			//debugVar("--line ".__LINE__." \n $_sql ;\n",0, array(1,__CLASS__.'--'.__FUNCTION__));
			$rs = self::ExecSql($_sql);
		}
		
		if (($rs->fields[0]) > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getTipoUsuarioByCod($cod) {
		$Usuario = new UsuarioModel();
		$GrupoPermissao = new UsuarioPermissaoGrupoModel();

		$Usuario->setCD_USUARIO(intval($cod));
		$Usuario->doFind();
		if ($Usuario->List->_numOfRows) {
			$GrupoPermissao->setCD_GRUPO($Usuario->getCD_GRUPO());
			$GrupoPermissao->doFind();
		}
		if ($GrupoPermissao->List->_numOfRows) {
			return $GrupoPermissao->getNM_GRUPO();
		} else {
			return false;
		}
		return $Usuario;
	}

	/**
	 *
	 * Retorna recordset do usuário
	 * @param int $CodGrupo| Código do Usuário
	 *
	 * @return array
	 */
	public static function getUsuarioByCod($CodUsuario){
		$Usuario = new UsuarioModel();
		$Usuario->setCD_USUARIO(intval($CodUsuario));
		//$Usuario->setDebug(true);
		$Usuario->doFind();
		return $Usuario;
	}
	/**
	 *
	 * Retorna recordset do usuário
	 * @param string $ds_login| ds login do Usuário
	 *
	 * @return array
	 */
	public static function &getUsuarioByDs_Login($ds_login){
		$Usuario = new UsuarioModel();
		$Usuario->setDS_LOGIN($ds_login);
		//$Usuario->setDebug(true);
		$Usuario->doFind();
		return $Usuario;
	}
	
	/**
	 *
	 * Retorna recordset do usuário
	 * @param string $ds_login| ds login do Usuário
	 *
	 * @return array
	 */
	public static function &getUsuarioByDs_LoginEhash($ds_login,$hash){
		$Usuario = new UsuarioModel();
		$Usuario->setDS_LOGIN($ds_login);
		$Usuario->setHASH_NV_SENHA($hash);
		//$Usuario->setDebug(true);
		$Usuario->doFind();
		return $Usuario;
	}
	/**
	 * Retorna a pessoa do usuário
	 * @param int $CodUsuario
	 * @return PessoaModel
	 */
	public static function getPessoaUsuarioByCod($CodUsuario){
		$usuario 	= self::getUsuarioByCod($CodUsuario);
		$pessoa 	= new PessoaModel();
		$pessoa->setCD_PESSOA( $usuario->getCD_PESSOA() );
		//$pessoa->setDebug(true);
		$pessoa->doFind();
		return $pessoa; 
	}
	/**
	 * Retorna a cidade do usuário
	 * @param int $CodUsuario
	 * @return array
	 */
	public static function getCidadeUsuarioByCod($CodUsuario){
		$pessoa = self::getPessoaUsuarioByCod($CodUsuario);
		if($pessoa->getCD_CIDADE()){
			$cidade = new CidadeModel();
			$cidade->setCD_CIDADE( $pessoa->getCD_CIDADE() );
			$cidade->doFind();
			return $cidade;
		} else {
			return null;
		}
	}
	/**
	 * Retorna o Grupo do Usuário
	 * @param int $codUser
	 */

	public function getGrupoUserByCodUser($codUser){
			
		$sql = "SELECT CD_GRUPO FROM USUARIO WHERE CD_USUARIO = ".$codUser;
		$rs = self::ExecSql($sql);
			
		return $rs;
	}
	
	/**
	 * Deleta o registro o Usuario
	 * @param int $cod
	 */
	public function doDeleteUsuario($Cod){
		$Usuario		= new UsuarioModel();
			
		$Usuario->doClearFields();
		$Usuario->setCD_USUARIO(intval($Cod));
		$Usuario->setAudit(true);
		$Usuario->doDelete("CD_USUARIO");
			
		unset($GET["delthis"]);
		unset($GET["cod"]);
	}
        
        /**
	 * Valida cadastro por CPF e Data de Nascimento

	 * @param int $cod
	 */
        public function doValidationCpfDtNascimento($GET) {
            $format 	= new formataCampoController();
				
            $whereStrAnd = "";
            $whereStrAnd2 = "";

            if($GET['nr_cpf'] != ''){
                $whereStrAnd = " AND p.nr_cpf = '".$format->cleanCpf( $GET['nr_cpf'] )."'";
            }

            if(!empty($GET['dt_nascimento'])) {
                $whereStrAnd2 = " AND p.dt_nascimento = '".$format->cleanDtNascimento( $GET['dt_nascimento'] )."'";
            }

            $SQL = "SELECT u.cd_usuario, u.ds_login, p.nm_pessoa, p.nr_cpf
                                    FROM usuario u, pessoa p 
                                    WHERE u.cd_grupo = {$GET['grupo']} {$whereStrAnd}{$whereStrAnd2} AND u.cd_pessoa = p.cd_pessoa";
            $usuarioRS = self::ExecSql($SQL);
            return $usuarioRS;
        }
        
        /**
         * Atualiza os campos para realizar a nova senha
         * @param string $ds_login
         * @param string $hash
         */
		public function doUpDateCampos_NovaSenha($ds_login){			
			$update = "upDate usuario u SET u.HASH_NV_SENHA ='" . sha1 ( uniqid ( mt_rand (), true ) ) . "', u.TEMPO_NV_SENHA = to_char(sysdate + 1 / 24,'yyyy-mm-dd hh24:mi:ss') WHERE u.ds_login = '{$ds_login}'";
			self::ExecSql($update);
		}
        
		/**
		 * Calcula o tempo do pedido da solicitacao ate o acesso via email.
		 *
		 * @access public
		 * @return mixed
		 */
		public function doCalculaTempo_AlteracaoSenha($usuario, $hashcode) {
			if(empty($usuario) || empty($hashcode)){
				return false;
			}else{
				$dados = $this->getUsuarioByDs_LoginEhash($usuario,$hashcode);
				if ($dados->List->_numOfRows == 0) {
					return false;
				}else{
					$dateTimeAnt = new DateTime($dados->List->fields['TEMPO_NV_SENHA']);
					$dateTimeAtual = new DateTime(date ( 'Y-m-d H:i:s' ));
					
		 			if ($dateTimeAnt->format ( 'Y' ) != $dateTimeAtual->format ( 'Y' ) || $dateTimeAnt->format ( 'm' ) != $dateTimeAtual->format ( 'm' )) {
						return false;
					} else {
						if ($dateTimeAnt->format ( 'd' ) < $dateTimeAtual->format ( 'd' )) {
							return false;
						} else {
							if(($dateTimeAtual->format ( 'H' ) - $dateTimeAnt->format ( 'H' )) >= 1){
								if(($dateTimeAtual->format ( 'H' ) - $dateTimeAnt->format ( 'H' )) > 1){
									$tempo_em_segundos = (((60 - $dateTimeAnt->format ('i'))*60) + ($dateTimeAtual->format ('i')*60));
									$tempo_em_segundos = ($tempo_em_segundos + (($dateTimeAtual->format ( 'H' ) - $dateTimeAnt->format ( 'H' ))*3600));
								}else{
									if($dateTimeAtual->format ( 'i' ) > $dateTimeAnt->format ( 'i' )){
										$tempo_em_segundos = (((60 - $dateTimeAnt->format ('i'))*60) + ($dateTimeAtual->format ('i')*60));
										$tempo_em_segundos = $tempo_em_segundos + 3600;
									}else{
										$tempo_em_segundos = (((60 - $dateTimeAnt->format ('i'))*60) + ($dateTimeAtual->format ('i')*60));
									}
								}
							}else{
								//se horas sao iguais, isso quer dizer q a hora ainda nao virou e q os minutos sao apenas crescentes
								$tempo_em_segundos = ($dateTimeAtual->format ( 'i' ) - $dateTimeAnt->format ( 'i' ))*60;
							}
							//var_dump($tempo_em_segundos);
							if ($tempo_em_segundos >= 3600) {
								return false;
							}
							return true;
						}
					}
				}
			}
		}
		/**
		 * Difine se requisicao pode ou nao pode construir a tela de nova sennha
		 */
		public function TelaNovaSenha($usuario,$hash) {
		 	return ($this->doCalculaTempo_AlteracaoSenha ($usuario,$hash)) ? true : false;
		}
		/**
		 * Funcao para alterar senha (funcao somente para recuperacao de senha)
		 */
		public function doAlterarSenha($post,$arr) {
			$dados = $this->getUsuarioByDs_LoginEhash($post->usuario,$post->hash);	
			if ($this->doEditarSenha($post)) {
				$this->doUpDateCampos_NovaSenha($post->usuario);
			}
			return $arr;
		}
		
		public function doEditarSenha($post){
			$usuario = $this->getUsuarioByDs_Login($post->usuario);
			$usuario->setDS_SENHA(md5($post->novasenha));
			$usuario->setAudit(true);
			$usuario->doUpdate();
			return true;
		}
		
		public function doValidaSenha($value){
			if(strlen($value) >=8 && strlen($value) <= 12){
				return preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $value);
			}else{
				return 0;
			}
		}
	
	/**
	 * Verifica e valida informações para usuário alterar senha e envia email 
	 * @param array $post
	 */
	public static function doRememberPassword($post){
			
	    $_isValid = false;
			
		$nrCpf            = $post['nr_cpf'];
		$dtNascimento     = $post['dt_nascimento'];
		$dsEmail          = $post['email'];
		$refCartao        = isset($post["referencia"]) ? $post["referencia"] : null;
		$msg              = "";
		
		$usuario  = new UsuarioController();
		$function = new CoreFunctions();
		
		if ( isset($refCartao ) && $refCartao ) {
		    
			$refCartao      = $post["referencia"];
			$indexToken     = $post["posicaochave"];
			$token          = $post["chave"];
		
			$msg = validaRefCartao( $refCartao );
		
			if ( !$msg ) {
			    
				$cartoes	= new CartaoController();
				$idCartao	= $cartoes->getIdCartaoByRef( $refCartao );
				
				if(!$idCartao) {
				    $msg .= "O cartão não foi encontrado!(2) <br />";
				} else {
					$cartao = new CartaoModel();
					$cartao->setID( $idCartao );
					$cartao->doFind();
					
					if ( $cartao->getSITUACAO_CARTAO() == "2" ) {
						$validate = false;
						$validate = $cartoes->doValidateCartao($indexToken, $idCartao, $token);			
						if ( $validate ){
							
							// Verifica se tem email antes de tentar enviar
							$cartaoAtivacaoPessoa = new CartaoAtivacaoPessoaModel();
							$cartaoAtivacaoPessoa->setCD_PESSOA();
							$cartaoAtivacaoPessoa->setCARTAO_ID( $idCartao );
							$cartaoAtivacaoPessoa->doFind();
							$codPessoa = $cartaoAtivacaoPessoa->getCD_PESSOA();
							
							$Pessoa = new PessoaController();
							$rsPessoa = $Pessoa->getPessoaByCod( $codPessoa );
							$email = trim($rsPessoa->getDS_EMAIL());
							
							if(!$email){
								$msg = "Não há email cadastrado. Favor digitar o e-mail correto ou cadastre-o na opção 3.";
							}else{
								if($email != $dsEmail){
									$msg = "CPF, Data Nascimento ou E-mail incorretos.";
								}
							}
							
							if( !$msg ){
							    // Procura o usuário vínculado ao cartão
							    $sql = "select cd_usuario, dt_ultimo_acesso from usuario u where u.ds_login = '$refCartao' and u.cd_pessoa = $codPessoa";
							    $rsUsuario = self::ExecSql($sql)->fields;
							    
							    if( sizeof($rsUsuario) > 0 ){
							        if(!$rsUsuario["DT_ULTIMO_ACESSO"]){
							            $msg = "Você não validou o primeiro acesso de Usuário servidor. Volte para tela inicial e realize a validação de primeiro acesso para usuário servidor para a sua primeira senha.";
							        }
							    } else {
							        $msg = "Não há usuário registrado para esse cartão Servfácil. Contacte o suporte.";
							    }							    
							}							
							
							if(!$msg){
								// Sem problemas no email
							    $rsEnvioEmail = $usuario->rememberUserAndPassword($post);
								$envioOk = $rsEnvioEmail["success"];
								if($envioOk){
									$_isValid = true;
									$msg = "Suas informações de usuário e senha foram enviadas com sucesso para o e-mail ".$dsEmail.". Favor clicar no botão 'CRIAR NOVA SENHA' do e-mail enviado para atualizar seus dados de acesso.";
								} else {
									$msg = $rsEnvioEmail["msg"];
								}
							}
						} else {
							$msg   = "Chave de segurança incorreta(5).";
						}
					} else {
						$dominio = new DominioController();
						$msg = "O cartão está \"".$dominio->getDominioByCodChave("SITUACAO_CARTAO", $cartao->getSITUACAO_CARTAO() )->getDS_VALOR()."\"";
					}
				}
			}
		} else {
		    
		    if (!$nrCpf || !$function->isValidCPF($nrCpf)) {
		        $msg .= "[- O CPF digitado não é válido!]";
		    }
		    
		    if (!$dtNascimento && !isValidDate($dtNascimento,"d/m/Y")) {
		        $msg .= "[A Data de Nascimento digitada não é válida!]";
		    } else {
			    $function = new CoreFunctions();
		        $dtNascimento  = $function->date2Db($dtNascimento);
		    }
		    
		    if(!$msg) {
			    
			    $format = new formataCampoController();
				//Verifica antes se tem email cadastrado
				$pessoa = new PessoaModel();
				$pessoa->setDS_EMAIL();
				$pessoa->setDT_NASCIMENTO();
				$pessoa->setNR_CPF( $format->cleanCpf( $post["nr_cpf"] ) );
				$pessoa->doFind();
				
				if( $pessoa->getCount() == 0 ){
				    $msg = "Cadastro inexistente!";
				}
				
				if(!$msg){
				    
    				$email = trim($pessoa->getDS_EMAIL());    				
    				if(!$email){
    					$msg .= "[Não há email cadastrado, favor digitar o e-mail correto, ou cadastre-o na opção 3.]";
    				}
    				
    				$dtNasc = $pessoa->getDT_NASCIMENTO();
    				if(!$dtNasc){
    				    $msg .= "[Impossível validar data de nascimento. Dados cadastrais incompletos!]";
    				}
				}
			    
				if(!$msg){
				    if($dtNasc != $dtNascimento  ){
    				    $msg .= "[A Data de Nascimento digitada não corresponde ao CPF informado!]";
    				}
    				
    				if($email != $dsEmail){
    					$msg .= "[CPF, Data Nascimento ou E-mail incorretos.]";
    				}				    
				}				
				
				if(!$msg){
					// Sem problemas no email , então envia
				    $post["codUsuario"] = $post["uPwsRec"];
				    
				    $rsEnvioEmail = $usuario->rememberUserAndPassword($post);
					$envioOk = $rsEnvioEmail["success"];
					if($envioOk){
						$_isValid = true;
						$html = "Suas informações de usuário e senha foram enviadas com sucesso para o e-mail \"$dsEmail\". Favor clicar no botão 'CRIAR NOVA SENHA' do e-mail enviado para atualizar seus dados de acesso.";
						$msg = $html;
					} else {
						$msg = $rsEnvioEmail["msg"];
					}
				}
			}				
		}
		
		$str["isValid"] = $_isValid;
		$str["html"]    = str_replace(array("][","[","]"),array("<br />","",""), $msg); 
		
		return $str;	
    }
		
	/**
	 * Função que envia o email de recuperação de senha
	 * @param array $GET
	 */
	private function rememberUserAndPassword($GET){
	    
	    $_success	= true;
	    
	    $format = new formataCampoController();
		
	    $where = $FromStr = "";
		
		if(!empty($GET['referencia'])) {
			
			$cartoes = new CartaoController();
			$idCartao = $cartoes->getIdCartaoByRef( $GET['referencia'] );
			$where .= "[ca.id = $idCartao  and cap.cd_pessoa = p.cd_pessoa AND u.cd_pessoa = p.cd_pessoa ]";
			$FromStr = " , cartao ca join cartao_ativacao_pessoa cap on cap.cartao_id = ca.id ";
		}
		
		if( isset($GET['nr_cpf']) && $GET['nr_cpf']){
        	$where .= "[p.nr_cpf = '".$format->cleanCpf( $GET['nr_cpf'] )."']";
		}
                
		if( isset($GET['dt_nascimento']) && $GET['dt_nascimento']) {
            $where .= "[p.dt_nascimento = '".$format->cleanDtNascimento( $GET['dt_nascimento'] )."']";
        }
        
        if(isset($GET['codUsuario']) && $GET['codUsuario'] ){
            $where .= "[u.cd_usuario = " . $GET['codUsuario']  ."]"; 
        }
        
        $where = (($where) ? " and " : "" ).parserWhereSql($where); 
        
        $SQL = "SELECT 
                    u.cd_usuario, u.ds_login, p.nm_pessoa, p.nr_cpf, u.HASH_NV_SENHA, u.TEMPO_NV_SENHA,
                    To_Char(To_Date( u.TEMPO_NV_SENHA, 'rrrr-mm-dd hh24:mi:ss'),'rrrrmmddhh24miss') dt_prazo_recuperacao
				FROM 
                    usuario u, 
                    pessoa p $FromStr
				WHERE 
                    u.cd_pessoa = p.cd_pessoa $where 
                AND p.ds_email = '{$GET['email']}'
                    ";
		
		//debugVar( $SQL, 0 , array(1, __CLASS__ .' -- ' . __FUNCTION__));
		$usuarioRS = self::ExecSql($SQL);
		
		if( $usuarioRS->_numOfRows > 0){
		    
		    $dtRecuperacao =  $usuarioRS->fields["DT_PRAZO_RECUPERACAO"];
		    if($dtRecuperacao){
		       $dtAual = date("YmdHis");
		       if( $dtRecuperacao > $dtAual ){
		           $_success	= false;
		           $msg		= "<div class='msg'><strong>Há uma recuperação de senha em andamento para esse usuário! </strong> <br /> Uma mensagem foi enviada anteriormente para o e-mail vinculado a pessoa desse usuário.</div>";
		       }
		    }
		    
		    if($_success){
		        
		        $destinatarios[] = $GET['email'];
    		    
    		    // Registra a NOVA hash
    			$this->doUpDateCampos_NovaSenha($usuarioRS->fields["DS_LOGIN"]);
    			
    			// Recupera a hash registrada
    			$usuarioRS = self::ExecSql($SQL);
    			
    			$linkHash = "<a style='display:block; text-decoration: none; color: #fff;' href='".CoreDefault::$DocumentUrl."?RecuperacaoSenha=1&usuario={$usuarioRS->fields['DS_LOGIN']}&hash={$usuarioRS->fields['HASH_NV_SENHA']}' title='Link para recupera&ccedil;&atilde;o de senha.' >CRIAR NOVA SENHA</a>";
    			
    			$_HashMsgMail = "<table align=\"center\" width=\"550\">
                            						<tr>
                                                        <td height=\"20\"></td>
                                                    </tr>
                            						<tr>
                            						      <td>
                                        						<p style=\"font-size: 20px; margin-bottom:0;\">Ol&aacute;, {$usuarioRS->fields["NM_PESSOA"]}</p>
                                        						<p>Voc&ecirc; solicitou recentemente a recupera&ccedil;&atilde;o dos seus dados de ingresso no <strong>Infoconsig</strong>.</p>
                                        						<p>Para sua seguran&ccedil;a clique no bot&atilde;o abaixo para que voc&ecirc; possa cadastrar uma nova senha.</p>
                                    					  </td>
                            						</tr>
                            						<tr>
                                                        <td height=20></td>
                                                    </tr>
                            						<tr>
                            							<td style=\"text-align: center;\">
                                                            <br />
                            								<table>
                            									<tr>
                            										<td style=\"padding: 15px; background-color: #1384AD; text-align: center;\">
                            											<strong>{$linkHash}</strong>
                            										</td>
                            								    </tr>
                            								</table>
                            							</td>
                            						</tr>
                            						<tr>
                                                        <td height=20></td></tr>
                            						<tr>
                                                        <td>
                                                            <table>
                                                                <tr>
                                                                    <td style=\"padding: 15px; text-align: justify; color:#c00; background-color: #FFE5E5;\">
                            											Observa&ccedil;&otilde;es: O link contido neste e-mail ter&aacute; validade de 1 hora,
                            											caso o prazo de altera&ccedil;&atilde;o de senha tenha ultrapassado o tempo estipulado, por favor
                            											solicite novamente a recupera&ccedil;&atilde;o da mesma.
                            										</td>
                            									</tr>
                            								</table>
                            							</td>
                            						</tr>
                                            </table>";
    			
    			$email = new GerenciaEmailController();
    			
    			$rs =  $email->doEnviarEmailRecuperacaoUsuarioSenha($senhaUsuario, $loginUsuario, $destinatarios, $_HashMsgMail);
    			
    			$_success = $rs["success"];
    			$msg      = $rs["msg"];		        
		    }			
		} else {
		    $_success = false;
		    $msg      = "<div class='msg'>Usuário não encontrado no sistema!</div>";
		}
		
		$return["success"]	= $_success;
		$return["msg"]		= $msg;
		
		return $return;		
	}
    
	/**
	 * 
	 * @param array $post
	 * @return string
	 */
	public static function doCompletaEmailUsuario($post){
	    
	    $str = array();
	    $isValid = false;
	    
        $msg = "";
	    $novoEmail     = $post["email"];
	    
	    if(!$novoEmail){
	        $msg = "E-mail não informado.";
	    }
	    
	    if(!$msg && !validaEMAIL( $novoEmail ) ){
	        $msg = "Email incorreto.";
	    }
	    
	    if(!$msg){
	        
    	    if(isset($post["referencia"]) && $post["referencia"]){
    	        
        	    $refCartao     = $post["referencia"];
    	        $indexToken    = $post["posicaochave"];
    	        $token         = $post["chave"];
    	        
                $cartoes = new CartaoController();
    	        
    	        $msg = validaRefCartao( $refCartao );
    	        if ( !$msg ) {
    	            $idCartao = $cartoes->getIdCartaoByRef( $refCartao );
    	            if( !$idCartao ) {
    	                $msg = "O cartão não foi encontrado!(10) <br />";	                
    	            }
    	        }
    	        
    	        if ( !$msg ) {
    	            $cartao = new CartaoModel();
    	            $cartao->setID( $idCartao );
    	            $cartao->doFind();
    	            if ( $cartao->getSITUACAO_CARTAO() != "2" ) {
    	                $dominio = new DominioController();
    	                $msg = "O cartão está \"".$dominio->getDominioByCodChave("SITUACAO_CARTAO", $cartao->getSITUACAO_CARTAO() )->getDS_VALOR()."\"";
    	            } else {
    	                $validate = $cartoes->doValidateCartao( $indexToken, $idCartao, $token );
    	                if(!$validate){
    	                    $msg   = "Chave de segurança incorreta.(9)";
    	                }
    	            }
    	        }
    	        
    	        if(!$msg){
    	            $usuario = new UsuarioModel();
    	            $usuario->setCD_PESSOA();
    	            $usuario->setDS_LOGIN( $refCartao );
    	            $usuario->doFind();
    	            
    	            $pessoa = new PessoaModel();
    	            $pessoa->setDS_EMAIL();
    	            $pessoa->setCD_PESSOA( $usuario->getCD_PESSOA() );
    	            $pessoa->doFind();
    	            
    	            $emailAnterior = $pessoa->getDS_EMAIL();
    	            
    	            if(trim($emailAnterior)){
    	                $msg = "Você já tem um e-mail registrado para recuperação de usuário/senha. Recupere o seu e-mail na opção 2.";
    	            } else {
    	                $pessoa->setDS_EMAIL($novoEmail);
    	                $pessoa->setCD_PESSOA( $usuario->getCD_PESSOA() );
    	                $pessoa->setAudit(true);
    	                $pessoa->doUpdate();
    	                $isValid   = true;
    	                $msg       = "<strong>Email cadastrado com sucesso.</strong> <br /> Agora você pode continuar na <strong>opção 1</strong>.";
    	            }
    	        }
    	        
    	    } else {
    	        
    	        $function = new CoreFunctions();
    	        
                $cpf           = $post["nr_cpf"];
                $dtNascimento  = $post["dt_nascimento"];
                $novoEmail     = $post["email"];
                
                if (!$cpf || !$function->isValidCPF($cpf)) {                    
                    $msg .= "[O CPF digitado não é válido!]";
                } 
                
                if (!$dtNascimento && !isValidDate($dtNascimento,"d/m/Y")) {                    
                    $msg .= "[A Data de Nascimento digitada não é válida!]";
                } else {
                    $dtNascimento  = $function->date2Db($dtNascimento);
                }
                
                if(!$msg){
                    
                    $PessoaController = new PessoaController();
                    $rsPessoa = $PessoaController->getPessoaByCPF($cpf);
                    $codPessoa      = $rsPessoa->getCD_PESSOA();
                    $emailAnterior  = $rsPessoa->getDS_EMAIL();
                    
                    //debugVar($rsPessoa);
                    if( $rsPessoa->getCount() == 0 ){
                        $msg = "Cadastro inexistente!";
                    }
                    
                    if(!$msg && !$rsPessoa->getDT_NASCIMENTO()){
                        $msg = "Impossível validar. Dados cadastrais incompletos!";
                    }
                    //debugVar( $dtNascimento . " __ " . $rsPessoa->getDT_NASCIMENTO() );
                    if(!$msg && $rsPessoa->getDT_NASCIMENTO() != $dtNascimento  ){
                        $msg = "A Data de Nascimento digitada não corresponde ao CPF informado!";
                    }
                    
                    
                    if(!$msg && trim($emailAnterior)){
                        $msg = "Você já tem um e-mail registrado para recuperação de usuário/senha. Recupere o seu e-mail na opção 2.";
                    }
                }
                    
                if(!$msg){
                    
                    $pessoa = new PessoaModel();
                    $pessoa->setCD_PESSOA( $codPessoa );
                    $pessoa->setDS_EMAIL( $novoEmail );
                    $pessoa->setAudit(true);
                    $pessoa->doUpdate();
                    $isValid    = true;
                    $msg        = "<strong>Email cadastrado com sucesso.</strong> <br /> Agora você pode continuar na <strong>opção 1</strong>.";
                                        
                }
    	    }
	    }
	    
	    $str["isValid"]    = $isValid;
	    $str["html"]       = str_replace(array("][","[","]"),array("<br />","",""), $msg);;
	    
	    return $str;
	}
	
	/**
	 * Função para recuperação de e-mail
	 * @param array $post
	 * @return array
	 */
	public static function getRememberEmail($post){
	    
	    $arrayReturn   = array();
	    $isValid       = false;
	    $msg           = "";
	    
	    $controller = new UsuarioController();
	    $function = new CoreFunctions();
	    
	    $cpf           = $post["nr_cpf"];
	    
	    $dtNascimento  = $post["dt_nascimento"];
	    	    
	    if (!$cpf || !$function->isValidCPF($cpf)) {
	        
	        $msg .= "[O CPF digitado não é válido!]";
	    }
	    
	    if (!$dtNascimento && !isValidDate($dtNascimento,"d/m/Y")) {
	        
	        $msg .= "[A Data de Nascimento digitada não é válida!]";
	    } else {
	        $dtNascimento  = $function->date2Db($dtNascimento);	        
	    }
	    
	    if(!$msg){
	        
	        $PessoaController = new PessoaController();
	        $rsPessoa = $PessoaController->getPessoaByCPF($cpf);
	        
	        if( $rsPessoa->getCount() == 0 ){
	            $msg = "Cadastro inexistente!";
	        } else {	            
	            $codPessoa = $rsPessoa->getCD_PESSOA();
	            $mail = $rsPessoa->getDS_EMAIL() ? trim($rsPessoa->getDS_EMAIL()) : '';
	        }
	        
	        if(!$msg && !$rsPessoa->getDT_NASCIMENTO()){
	            $msg = "Impossível validar data de nascimento. Dados cadastrais incompletos!";
	        }
	        
	        if(!$msg && $rsPessoa->getDT_NASCIMENTO() != $dtNascimento  ){
	            $msg = "A Data de Nascimento digitada não corresponde ao CPF informado!";
	        }
	        
	        if(!$msg && !$mail ){
	            $msg = "Não há email cadastrado. Favor, cadastre-o na opção 3.";
	        } else {
	            $email = $rsPessoa->getDS_EMAIL();
	        }
	        
    	    if(!$msg){    	        
    	        $sql = "SELECT Count(1) qtd FROM usuario u, pessoa p WHERE u.cd_pessoa = $codPessoa and u.cd_pessoa = p.cd_pessoa ";
    	        $rsUserPessoa = self::ExecSql($sql);
    	        if( 0 == $rsUserPessoa->fields["QTD"] ){
    	            $msg = "Não há usuário para recuperação de senha para estes parâmetros.";
    	        } else {
    	            $isValid   = true;
    	            $msg       = "O email cadastrado é: <strong>$email</strong>";
    	        }
    	    }
	    }
	    
	    $arrayReturn["isValid"] = $isValid;
	    $arrayReturn["html"] = str_replace(array("][","[","]"),array("<br />","",""), $msg);;
	    return $arrayReturn;
	}

	/**
	 *
	 * Retorna uma lista de novos usuários criados automaticamente com senha e login predefinidos. 
	 * Os usuários criados somente para determinado grupo de permissão que ainda não tenham sido criados anteriormente 
	 * @param int $codGrupo | Código do grupo de permissão
	 * @param array $setGrupo | Array com os parametros definidos conforme o grupo
	 */
	public function getNewListUsuarios( $codGrupo, $setGrupo = array() ){
		$functions = new CoreFunctions();
		// A senha é estática e igual para todos por enquanto até outra definição		
		switch ( $codGrupo ) {
			case 48 :
				$codConsignanteMaster = $setGrupo["CD_CONSIGNANTE_MASTER"];
				$codConsignante		  = $setGrupo["CD_CONSIGNANTE"];
				$codAverbador 		  = $setGrupo["CD_AVERBADOR"];
				// Verificando servidores que ainda não tenham usuários para acesso ao sistema
			    // O servidor precisa estar associado a uma pessoa obrigatoriamente
				$sql = "SELECT
								  * 
								FROM (
								        SELECT
								          serv.cd_pessoa, 
								          serv.cd_servidor, 
								          us.cd_usuario , 
								          serv.nr_matricula, 
								          pes.nm_pessoa nm_servidor,   
								          us.ds_login ,								          								          
								          serv.cd_consignante_master, 
								          serv.cd_consignante, 
								          serv.cd_averbador,
								          pes.ds_email,
								          (serv.cd_consignante ||'.'|| serv.nr_matricula) ds_new_login,
								          '12345' ds_new_senha
								        FROM 
								          servidor serv  
								          left JOIN usuario us ON us.cd_servidor = serv.cd_servidor 
								                              AND us.cd_consignante_master = serv.cd_consignante_master 
								                              AND us.cd_consignante = serv.cd_consignante 
								                              AND us.cd_averbador = serv.cd_averbador
								                              AND us.cd_grupo = $codGrupo
								           , pessoa pes  
								        WHERE
								            serv.cd_consignante_master = $codConsignanteMaster
								        AND serv.cd_consignante = $codConsignante
								        AND serv.cd_averbador = $codAverbador
								        AND pes.cd_pessoa = serv.cd_pessoa
								        ORDER BY Nls_Upper( nm_servidor )
								      ) subQ
								WHERE subQ.ds_login IS null	";	
				
				$rs = self::ExecSql($sql);
				// Gravando senha padrão e usuários combinando código do consignante + matricula do servidor
				$Usuario  = new UsuarioModel();
				$Security = new CoreWebSecurity();
				if( $rs->_numOfRows > 0 ) { 
					while (!$rs->EOF){
						
						$Usuario->doClearFields();
						$Usuario->setCD_USUARIO($Usuario->getMax("CD_USUARIO")+1);
						$Usuario->setCD_PESSOA(            $rs->fields['CD_PESSOA']);
						$Usuario->setCD_GRUPO(             $codGrupo );
						$Usuario->setDS_LOGIN(             $rs->fields["DS_NEW_LOGIN"]);
						$Usuario->setCD_AVERBADOR(         null);
						$Usuario->setCD_CONSIGNANTE_MASTER(null);
						$Usuario->setCD_CONSIGNANTE(       null);
						$Usuario->setCD_CONSIGNATARIA(     null);
						$Usuario->setCD_FILIAL(            null);

						$Usuario->setCD_CONSIGNANTE_MASTER($rs->fields["CD_CONSIGNANTE_MASTER"]);
						$Usuario->setCD_CONSIGNANTE(       $rs->fields["CD_CONSIGNANTE"]);
						$Usuario->setCD_AVERBADOR(         $rs->fields["CD_AVERBADOR"]);
						$Usuario->setCD_SERVIDOR(          $rs->fields["CD_SERVIDOR"]);
						
						$Usuario->setCD_PESSOA(            $rs->fields["CD_PESSOA"] );
						//$Usuario->setDS_EMAIL(             $rs->fields['DS_EMAIL']);
						$Usuario->setDS_SENHA(             $Security->doMd5($rs->fields['DS_NEW_SENHA']));
						$Usuario->setID_ATIVO(             '1');
						$Usuario->setDT_INSERT(            date("Ymdhi"));
						$Usuario->setCD_USUARIO_INSERT(    $_SESSION[PROJECT_FOLDER]["AUTH"]["CD_USUARIO"]);
						$Usuario->setAudit(TRUE);
						$Usuario->doInsert();
						$rs->MoveNext();
					}				
				}
				// Voltando ao ponteiro inicial para o relatório
				$rs->MoveFirst();
				break;
			default :
				null;
				break;
		}
		return $rs;
	}
	
	/**
	 * Versão public de self::getItensGruposByCodGrupo
	 * @param array $codGrupo
	 */
	private function getItensGrupo($codGrupo){
		return $this->getItensGruposByCodGrupo($codGrupo); 
	}
	
	/**
	 * Retorna uma lista de itens cadastrado no sistema para o grupo desejado e informado pelo código
	 * @param int $codGrupo | Código do grupo desejado
	 * @return array
	 */
	private function getItensGruposByCodGrupo($codGrupo){
	    
	    $sessaoLogOn = isset($_SESSION[PROJECT_FOLDER]["AUTH"]) ? $_SESSION[PROJECT_FOLDER]["AUTH"] : null;
	    
		$arrayItens = array();
		switch ($codGrupo){
			case 1 :
				//Administrador
				$arrayItens["NM_ITEM"] = "...";
				break;
			case 48 :
				// Servidor - Lista as consignantes cadastradas para usar no filtros de usuários de servidores destas
				$arrayItens["NM_ITEM"] = "uma Consignante";
				$consignanteMaster = new ConsignanteMasterModel();
				$consignanteMaster->setNM_CONSIGNANTE_MASTER()->doSortAsc();
				$consignanteMaster->doFind();
				if($consignanteMaster->getCount() > 0){
					while(!$consignanteMaster->EOF){
						$consignante = new ConsignanteModel();
						$consignante->setCD_CONSIGNANTE_MASTER( $consignanteMaster->getCD_CONSIGNANTE_MASTER() );
						$consignante->setNM_CONSIGNANTE()->doSortAsc();
						$consignante->doFind();
						if($consignante->getCount() > 0){
							while(!$consignante->EOF){
								$array = array();
								$array["CD_ITEM_GRUPO"] = $consignante->getCD_CONSIGNANTE();
								$array["DS_ITEM"] = $consignanteMaster->getNM_CONSIGNANTE_MASTER() . " | ". $consignante->getNM_CONSIGNANTE();
								$arrayItens["ITENS"][] = $array;
								$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
								$consignante->MoveNext();						
							}							
						}						
						$consignanteMaster->MoveNext();
					}					
				}
				break;
			case 61 :
				//Consignataria
				$arrayItens["NM_ITEM"] = "uma Consignatária";
				$consignataria = new ConsignatariaModel();
				$consignataria->setNM_CONSIGNATARIA()->doSortAsc();
				$consignataria->doFind();
				if($consignataria->getCount() > 0){
					while(!$consignataria->EOF){
						$array = array();
						$array["CD_ITEM_GRUPO"] = $consignataria->getCD_CONSIGNATARIA();
						$array["DS_ITEM"] = $consignataria->getNM_CONSIGNATARIA() . " | ". $consignataria->getNM_FANTASIA();
						$arrayItens["ITENS"][] = $array;
						$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
						$consignataria->MoveNext();
					}					
				}
				break;
			case 62 :
			    //Filial Consignataria
				$arrayItens["NM_ITEM"] = "uma Consignatária Filial";
				$consignataria = new ConsignatariaModel();
				$consignataria->setNM_CONSIGNATARIA()->doSortAsc();
			    if( isset( $sessaoLogOn['CD_CONSIGNATARIA'] ) ){
			        $consignataria->setCD_CONSIGNATARIA( $sessaoLogOn['CD_CONSIGNATARIA'] );			        
			    }
				$consignataria->doFind();
				if($consignataria->getCount() > 0 ){
					while(!$consignataria->EOF){
						$consignatariaFilial = new ConsignatariaFilialModel();
						$consignatariaFilial->setCD_CONSIGNATARIA( $consignataria->getCD_CONSIGNATARIA() );
						$consignatariaFilial->setNM_FILIAL()->doSortAsc();
						$consignatariaFilial->doFind();
						if( $consignatariaFilial->getCount() > 0){
							while(!$consignatariaFilial->EOF){
								$array = array();
								$array["CD_ITEM_GRUPO"] = $consignatariaFilial->getCD_FILIAL();
								$array["DS_ITEM"] = $consignataria->getNM_CONSIGNATARIA() ." | ". $consignatariaFilial->getNM_FILIAL();
								$arrayItens["ITENS"][] = $array;
								$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
								$consignatariaFilial->MoveNext();
							}						
						}
						$consignataria->MoveNext();
					}
				}
				break;
			case 63 :
				//Correspondente Master
				$arrayItens["NM_ITEM"] = "um Correspondente Master";
				$correspondente  = new CorrespondenteModel();
				$correspondente->setCD_CORRESPONDENTE_MASTER(0);
				$correspondente->setNM_CORRESPONDENTE()->doSortAsc();
				$correspondente->doFind();
				if($correspondente->getCount() > 0){
					while(!$correspondente->EOF){
						$array = array();
						$array["CD_ITEM_GRUPO"] = $correspondente->getCD_CORRESPONDENTE();
						$array["DS_ITEM"] = $correspondente->getNM_CORRESPONDENTE();
						$arrayItens["ITENS"][] = $array;
						$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
						$correspondente->MoveNext();
					}					
				}
				break;
			case 64 :
				//Consignante
				$arrayItens["NM_ITEM"] = "uma Consignante";
				$consignanteMaster = new ConsignanteMasterModel();
				$consignanteMaster->setNM_CONSIGNANTE_MASTER()->doSortAsc();
				$consignanteMaster->doFind();
				if($consignanteMaster->getCount() > 0 ){
					while(!$consignanteMaster->EOF){
						$consignante = new ConsignanteModel();
						$consignante->setCD_CONSIGNANTE_MASTER( $consignanteMaster->getCD_CONSIGNANTE_MASTER() );
						$consignante->setNM_CONSIGNANTE()->doSortAsc();
						$consignante->doFind();
						if($consignante->getCount() > 0){
							while(!$consignante->EOF){
								$array = array();
								$array["CD_ITEM_GRUPO"] = $consignante->getCD_CONSIGNANTE();
								$array["DS_ITEM"] = $consignanteMaster->getNM_CONSIGNANTE_MASTER() . " | ". $consignante->getNM_CONSIGNANTE();
								$arrayItens["ITENS"][] = $array;						
								$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
								$consignante->MoveNext();						
							}
						}
						$consignanteMaster->MoveNext();
					}					
				} 
					
				break;
			case 65 :
				//Averbador
				$arrayItens["NM_ITEM"] = "um Averbador";
				//$consignanteMaster = new ConsignanteMasterModel();
				//$consignanteMaster->setNM_CONSIGNANTE_MASTER()->doSortAsc();
				//$consignanteMaster->doFind();
				//while(!$consignanteMaster->EOF){
				
					$consignante = new ConsignanteModel();
					$consignante->setNM_CONSIGNANTE()->doSortAsc();
					$consignante->doFind();
					if($consignante->getCount() > 0){					
						while(!$consignante->EOF){
							$averbador = new AverbadorModel();
							$averbador->setCD_CONSIGNANTE( $consignante->getCD_CONSIGNANTE() );
							$averbador->setNM_AVERBADOR()->doSortAsc();
							$averbador->doFind();
							if($averbador->getCount() > 0){
								while(!$averbador->EOF){
									$array = array();
									$array["CD_ITEM_GRUPO"] = $averbador->getCD_AVERBADOR();
									$array["DS_ITEM"] = $consignante->getNM_CONSIGNANTE() . " | ". $averbador->getNM_AVERBADOR();
									$arrayItens["ITENS"][] = $array;
									$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
									$averbador->MoveNext();
								}								
							}
							$consignante->MoveNext();						
						}
					}
				//	$consignanteMaster->MoveNext();
				//}
				break;
			case 66 :
				//Filial Correspondente
				$arrayItens["NM_ITEM"] = "um Correspondente Filial";
				// Master				
				$correspondente  = new CorrespondenteModel();
				$correspondente->setCD_CORRESPONDENTE_MASTER(0);
				$correspondente->setNM_CORRESPONDENTE()->doSortAsc();
				$correspondente->doFind();
				if($correspondente->getCount() > 0){
					while(!$correspondente->EOF){
						// Filial
						$correspondenteFilial = new CorrespondenteModel();
						$correspondenteFilial->setCD_CORRESPONDENTE_MASTER( $correspondente->getCD_CORRESPONDENTE() );
						$correspondenteFilial->setNM_CORRESPONDENTE()->doSortAsc();
						$correspondenteFilial->doFind();
						if( $correspondenteFilial->getCount() > 0 ) {
							while(!$correspondenteFilial->EOF){
								$array = array();
								$array["CD_ITEM_GRUPO"] = $correspondenteFilial->getCD_CORRESPONDENTE();
								$array["DS_ITEM"] = $correspondente->getNM_CORRESPONDENTE() . " | " . $correspondenteFilial->getNM_CORRESPONDENTE();
								$arrayItens["ITENS"][] = $array;
								$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
								$correspondenteFilial->MoveNext();
							}
						}
						$correspondente->MoveNext();
					}
				}
				break;
			case 67 :
				//PDV - apenas consignatárias com tipo de consignação 2(Convênios Diversos) ou 8(Serfácil)
				//$arrayItens["NM_ITEM"] = "uma Consignatária";
				$arrayItens["NM_ITEM"] = "uma Credendeciada";
				//$sql = "select c.cd_consignataria, c.nm_fantasia, c.nm_consignataria from consignataria c where c.tipo_consignacao in ( 2 , 8 ) order by upper( c.nm_consignataria ), upper( c.nm_fantasia )";
				//$sql = "select c.cd_consignataria, c.nm_fantasia, c.nm_consignataria from consignataria c where c.tipo_consignacao in ( 2 , 8 ) order by upper( c.nm_fantasia )";
				$sql = "select c.cd_consignataria, c.nm_fantasia, c.nm_consignataria from consignataria c where c.tipo_consignacao in ( 2 , 8 ) order by upper( c.nm_fantasia )";
				$rsConsignatariaPdv = self::ExecSql($sql);				
				if($rsConsignatariaPdv->_numOfRows > 0){
					while(!$rsConsignatariaPdv->EOF){
						$array = array();
						$array["CD_ITEM_GRUPO"] = $rsConsignatariaPdv->fields["CD_CONSIGNATARIA"];
						//$array["DS_ITEM"] = $rsConsignatariaPdv->fields["NM_CONSIGNATARIA"] . " | ". $rsConsignatariaPdv->fields["NM_FANTASIA"];
						$array["DS_ITEM"] = $rsConsignatariaPdv->fields["NM_FANTASIA"];
						$arrayItens["ITENS"][] = $array;
						$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
						$rsConsignatariaPdv->MoveNext();
					}
				}				
				break;
			case 68 :
				//Agencia Master
				$arrayItens["NM_ITEM"] = "uma Agência";
				$agencia = new AgenciaModel();
				$agencia->setNM_AGENCIA()->doSortAsc();
				$agencia->doFind();
				if($agencia->getCount() > 0){
					while(!$agencia->EOF){
						$array = array();
						$array["CD_ITEM_GRUPO"] = $agencia->getCD_AGENCIA();
						$array["DS_ITEM"] = $agencia->getNM_AGENCIA();
						$arrayItens["ITENS"][] = $array;
						$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
						$agencia->MoveNext();
					}
				}
				break;
			case 69 :
				// Agência Filial
				$arrayItens["NM_ITEM"] = "uma Agência Filial";
				$agencia = new AgenciaModel();
				$agencia->setNM_AGENCIA()->doSortAsc();
				$agencia->doFind();
				if($agencia->getCount() > 0){
					$AgenciaFilial = new AgenciaFilialController();
					while(!$agencia->EOF){
						$rsAgenciaFilial = $AgenciaFilial->getArrayAgenciaByAgenciaMaster( $agencia->getCD_AGENCIA() , 0);
						while (!$rsAgenciaFilial->EOF){
							$array = array();
							$array["CD_ITEM_GRUPO"] = $rsAgenciaFilial->fields["CD_AGENCIA"];
							$array["DS_ITEM"] = $rsAgenciaFilial->fields["NM_AGENCIA"];
							$arrayItens["ITENS"][] = $array;
							$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
							$rsAgenciaFilial->MoveNext();
						}
						$agencia->MoveNext();
					}
				}
				break;				
			case 201 :
				//Grupo para ativação de cartão Servfacil
				$arrayItens["NM_ITEM"] = "uma Consignante";
				$consignanteMaster = new ConsignanteMasterModel();
				$consignanteMaster->setNM_CONSIGNANTE_MASTER()->doSortAsc();
				$consignanteMaster->doFind();
				if($consignanteMaster->getCount() > 0){
					while(!$consignanteMaster->EOF){
						$consignante = new ConsignanteModel();
						$consignante->setCD_CONSIGNANTE_MASTER( $consignanteMaster->getCD_CONSIGNANTE_MASTER() );
						$consignante->setNM_CONSIGNANTE()->doSortAsc();
						$consignante->doFind();
						if($consignante->getCount() > 0){
							while(!$consignante->EOF){
								$array = array();
								$array["CD_ITEM_GRUPO"] = $consignante->getCD_CONSIGNANTE();
								$array["DS_ITEM"] = $consignanteMaster->getNM_CONSIGNANTE_MASTER() . " | ". $consignante->getNM_CONSIGNANTE();
								$arrayItens["ITENS"][] = $array;
								$arrayItens["LISTA"][ $array["CD_ITEM_GRUPO"] ] = $array["DS_ITEM"];
								$consignante->MoveNext();						
							}							
						}						
						$consignanteMaster->MoveNext();
					}					
				}
				break;
					
		} 
		//debugVar($arrayItens);
		return $arrayItens;
	}
	
	/**
	 * Verifica se é o primeiro acesso pelo data de ultimo acesso. Se estiver vazio então é o primeiro acesso. Implementado para identificar o primeiro somente do grupo servidor.
	 * @param string $dsLogin | login do acesso
	 * @return number
	 */
	public static function doVerificaPrimeiroLogin($post){
		$msg = "";
		// Primeiro acesso é uma verificação somente para servidor por enquanto - 23/09/2014
		$firstAccess = 0;
		$dsLogin = ($post["login"])?$post["login"]: ( isset($post["REFERENCIA"])?$post["REFERENCIA"]:null );
				
		if($dsLogin){
			$sql = "select
						us.ds_login , 
					    Decode(us.dt_ultimo_acesso, NULL, 1, 0 ) primeiro_acesso , 
					    ca.situacao_cartao  
					from
						usuario us
					    inner join cartao_ativacao_pessoa cp on cp.cd_pessoa = us.cd_pessoa
					    inner join cartao ca on ca.id = cp.cartao_id AND LPad(ca.referencia,6,'0') = LPad(SubStr(us.ds_login,1, InStr(us.ds_login,'-')-1),6,'0')
					where
						us.cd_grupo = 48
					and us.ds_login = '$dsLogin'
					and us.id_ativo = 1";
			
			//debugVar("--line ".__LINE__." \n $sql ;\n",0, array(1,__CLASS__.'--'.__FUNCTION__));
			$rsUsuarioCartaoAcesso = self::ExecSql($sql);
			if( $rsUsuarioCartaoAcesso->_numOfRows > 0){
				$dsSituacaoCartao = $rsUsuarioCartaoAcesso->fields["SITUACAO_CARTAO"];				
				if($dsSituacaoCartao){
					switch ($dsSituacaoCartao){
						case "1":
							$msg = "O cartão não está designado.";
							break;
						case "2":
							$firstAccess = $rsUsuarioCartaoAcesso->fields["PRIMEIRO_ACESSO"];
							if( !$firstAccess && isset($post["REFERENCIA"]) && isset($post["PRIMEIRO_ACESSO"]) ){
								// Consulta a partir da tela de primeiro acesso, se a consulta for a partir da tela de primeiro acesso uma mensagem é retorna avisando que já houve acesso
								$msg = 'Este não é o seu primeiro acesso. Volte para a tela anterior.';
							}elseif( $firstAccess && isset($post["login"]) ){
								// Aviso de redirecionamento para a página de primeiro acesso
								$msg = '<br />Este é o seu primeiro acesso! <br />Você será redirecionado para o <strong>Primeiro Acesso</strong> na próxima tela.';
							} 
							break;
						case "3":
							$msg = "Cartão cancelado. Entre em contato com o suporte do sistema.";
							break;
					}
				}
			}			
		}
		$result["firstAccess"]	= $firstAccess;
		$result["msg"]			= $msg;
		return $result;
	}
	
	/**
	 * Verifica se a pessoa em questão já possui um usuario do grupo servidor
	 * @param Int$codPessoa
	 */
	public function doVerificaUsariogrupoServidor ($codPessoa){
		
		$sql = "
				SELECT
					count(*) qtde
				FROM
					USUARIO us
				where
					US.CD_PESSOA = $codPessoa
				and us.cd_grupo = 48
				";

		$rs  = self::ExecSql($sql);
		return $rs->fields["QTDE"];
		
	}
	
	
	/**
	 * Recupera o registro de usuário do grupo servidor pelo código de pessoa
	 * @param number $codPessoa
	 * @return CoreDbTable
	 */
	public function getUsuarioServidorByCodPessoa($codPessoa){
	
		$sql = " 
				SELECT
					us.*
				FROM
					USUARIO us
				where
					US.CD_PESSOA = $codPessoa
				AND us.cd_grupo = 48
				";
		
		$rs  = self::ExecSql($sql);
	
		return $rs;	
	}
	
	/**
	 * Retorna a data de ultimo acesso de um usuario, por default um usuario servidor.
	 * 
	 * @param number $codPessoa | Código da pessoa
	 * @return null|boolean
	 */
	public function doVerificaUsuarioServidorDtUltimoAcessoByPessoa($codPessoa){
		
		$sql = "
				select 
					DT_ULTIMO_ACESSO
				from 
					usuario
				where 
						cd_pessoa = $codPessoa
					and dt_ultimo_acesso is not null
					and	cd_grupo = 48
				";
		
		$rs  = self::ExecSql($sql);
		
		if ($rs->_numOfRows > 0){
			return $rs->fields["DT_ULTIMO_ACESSO"];
		}else{
			return false;
		}
	}
	
	
	/**
	 * Retorna um array de todos os menus ativos para a Empresa e Grupo passado..
	 * @param Int $codGrupo
	 * @param Int $codEmpresa
	 * @return array
	 */
	public function getPermissaoItemsByCodGrupoAndCodEmpresa($codGrupo,$codEmpresa){
		if($codGrupo){
		    
		    $whereEmpresa = "";
		    
			if($codEmpresa){
				switch ($codGrupo){
					case 61 : 
						//Consignataria
						$whereEmpresa = "and vp.cd_consignataria = $codEmpresa ";
						break;
					case 62 :
						//Filial Consignataria
						$whereEmpresa = "and vp.cd_filial = $codEmpresa ";
						break;
					case 68 : 
						//Agencia
						$whereEmpresa = "and vp.cd_agencia = $codEmpresa ";
						break;
					case 69 :
						//Agencia Filial
						$whereEmpresa = "and vp.cd_agencia = $codEmpresa ";
						break;
					case 65 :
						//Averbador
						$whereEmpresa = "and vp.cd_averbador = $codEmpresa ";
						break;
					case 64 :
						//Consignante
						$whereEmpresa = "and vp.cd_consignante = $codEmpresa ";
						break;
				}
			}
			
			$sql = "	
					select
						distinct 
			       			upi.cd_menu,
			       			um.cd_menu_pai
					from
						usuario_permissao up,
		        		vinculo_perfil vp,
			        	usuario_permissao_item upi,
			        	usuario_menu um
					where
							up.cd_grupo = $codGrupo
						$whereEmpresa
						and up.id_ativo = 1
				        and vp.cd_permissao = up.cd_permissao
				        and vp.id_ativo = 1
				        and upi.cd_permissao = vp.cd_permissao
				         and um.cd_menu = upi.cd_menu
	             	order by um.cd_menu_pai
					";
			//debugVar("--line ".__LINE__." \n $sql ;\n",0, array(1,__CLASS__.'--'.__FUNCTION__));
			$rs  = self::ExecSql($sql);
			return $rs;
		}
		return null;		
	}
	
	/**
	 * Recupera informações sobre os menus filhos apartir do codigo de menu pai
	 * @param Int $codMenu
	 * @return array
	 */
	public function getListMenuFilhobyCodMenuPai($codMenu){
		
		$sql = "
			select 
				*
			from
				usuario_menu um
			where
				um.cd_menu_pai = $codMenu
				and um.id_ativo = 1
				and um.id_visivel = 1
				";
		//debugVar("--line ".__LINE__." \n $sql ;\n",0, array(1,__CLASS__.'--'.__FUNCTION__));
		$rs  = self::ExecSql($sql);
		return $rs;
		
	}
	
	/**
	 * Função responsável por retornar o nome do menu em questão...
	 * @param Int $codMenu
	 * @return String
	 */
	public function getNomeMenuByCodMenu($codMenu){
		if($codMenu){
			$sql = "
					SELECT 
						NM_MENU
					FROM
						USUARIO_MENU
					WHERE
						CD_MENU = $codMenu				
					";
			//debugVar("--line ".__LINE__."\n$sql\n;",0,array(1,__CLASS__.' -- '.__FUNCTION__));
			$rs  = self::ExecSql($sql);		
			return $rs->fields["NM_MENU"];
		}
		return null;
	}
	
	/**
	 * Retorna o nome do meu pai do menu em questão.
	 * @param Int $codMenu
	 * @return String
	 */
	public function getNomeMenuPaiBycodMenu($codMenu){
		if($codMenu){
			$sql = "
					select
						NM_MENU 
					from
						usuario_menu um
					where
						um.cd_menu  = (select cd_menu_pai from
										usuario_menu um
										where
										um.cd_menu = $codMenu)
					";
			//debugVar("--line ".__LINE__."\n$sql\n;",0,array(1,__CLASS__.' -- '.__FUNCTION__));
            $rs  = self::ExecSql($sql);
            if($rs->_numOfRows > 0){
    			return $rs->fields["NM_MENU"];                
            }
		}
		return null;
	}
	
	/**
	 * Funação que retorna se o menu em questão esta habilitado para p Perfil em questão
	 * @param Int $codMenu
	 * @param Int $codPerfil
	 * @return boolean
	 */
	public function doVerificaMenuByPerfil($codMenu, $codPerfil){
		if(!isEmptyVars(array($codMenu, $codPerfil))){
			$sql = "
					SELECT 
						count (*) count
					FROM
						vinculo_perfil VP,
						usuario_permissao_item upi
					where
						     vp.id_perfil = $codPerfil
						and  upi.cd_permissao = vp.cd_permissao
	 					and upi.cd_menu = $codMenu";
		
			//debugVar("--line ".__LINE__."\n$sql\n;",0,array(1,__CLASS__.' -- '.__FUNCTION__));
			$rs  = self::ExecSql($sql);
			if($rs->_numOfRows > 0){
    			return $rs->fields["COUNT"];			    
			}
		}
		return null;
	}
    
	/**
	 * Retorna a quantidade total de registros encontrados de usuários
	 * @param array $post
	 * @return array
	 */
	public function getCountUsuarios( $post ){
		$sql = $this->getParserSqlUsuarios( $post );
		$sql = "SELECT 
				    Count(*) qtd_usuarios 
				FROM ( $sql )";
		//debugVar("--line ".__LINE__." \n $sql ;\n",0, array(1,__CLASS__.'--'.__FUNCTION__));
		return self::ExecSql( $sql );
	}
	
	/**
	 * 
	 * @param number $codUsuario
	 */
	public static function doInsertDtOnline($codUsuario){
		if($codUsuario){
			$sql = "update usuario set dt_online = ".date('YmdHis')." where cd_usuario = $codUsuario";
			self::ExecSql($sql);			
		}
	}
		
	/**
	 * Retorna registros de sessão do usuário no banco de dados 
	 * @param string $idSession
	 * @param number $codUsuario
	 */
	private static function getStatusSessao( $idSession, $codUsuario ){
		if(!isEmptyVars(array($idSession, $codUsuario))){
			$dt_expiracao = "dt_online";
			$validacoes = $_SESSION[PROJECT_FOLDER]["AUTH"]["VALIDACOES"];
			if($validacoes){
				if(!$_SESSION[PROJECT_FOLDER]["AUTH"]["dt_expiracao"]){
					// A primeira vez registra o momento da tentativa de validação
					$_SESSION[PROJECT_FOLDER]["AUTH"]["dt_expiracao"] = date("YmdHis");					
				}
				$dt_expiracao = $_SESSION[PROJECT_FOLDER]["AUTH"]["dt_expiracao"];
			}
			$sql = "select 
						id_status, 
						id_session, 
						dt_online, 
						To_Char(SYSDATE,'rrrrmmddhh24miss') dt_online_atual, 
						Round((SYSDATE - To_Date( $dt_expiracao ,'rrrrmmddhh24miss'))*24*60*60) nr_tempo_expiracao
						,
					    case 
							when id_status = 2 then
								5
							when dt_online is null and id_session is null  then
								4
							when id_status = 1 and id_session = '$idSession' then
								1
							when id_status = 1 and id_session != '$idSession' then
								2
							when id_status = 0 and id_session = '$idSession' then
								3
							when id_status = 0 and id_session != '$idSession' then
								4
						end status_online		
					from 
						usuario 
					where 
						cd_usuario = $codUsuario";
			
			//debugVar("--line:".__LINE__.":\n$sql\n;", 0, array( 1, __CLASS__ . '--' . __FUNCTION__));
			$rsSessao 		= self::ExecSql($sql);
		}
		return $rsSessao; 
	}
	
	/**
	 * Verifica a situação das sessões do usuário enquanto logado. Responde com status e gera a nova ID para acesso 
	 * @return number[]
	 */
	public static function online( $nav = null ){ 
		$msgLoginStatus = null;
		$arrayReturn = array();
		$status = 0;
		$gerarIdSessao = 0;
		$notify = 0;
		
        if(isset($_SESSION[PROJECT_FOLDER]["AUTH"])){
            
            $sessao = $_SESSION[PROJECT_FOLDER]["AUTH"];
    		$validacoes = isset($sessao["VALIDACOES"]) ? $sessao["VALIDACOES"] : false ;
    		$codUsuario = isset($sessao["CD_USUARIO"]) ? $sessao["CD_USUARIO"] : null ;
    		
    		$idSession = session_id();
    		
    		if($validacoes){
    			// Colocar tempo de expiração para validar o cadastro de usuário e alteração de senha
    			if($codUsuario){
    				$rsSessao = self::getStatusSessao($idSession, $codUsuario);
    				$nrTempoExpiracao	= $rsSessao->fields["NR_TEMPO_EXPIRACAO"];
    				if( $nrTempoExpiracao > (TEMPO_EXPIRACAO_SESSAO * 3 )){
    					// Para essa situação expira quando se fecha a aba sem fazer logoff ou quando há problema de conexão de Internet
    					$status = 2;
    					$msgLoginStatus = "Sessão expirada! [1]";
    					unset($_SESSION[PROJECT_FOLDER]);
    					$gerarIdSessao = 1;
    				}
    			}
    		}else{	
    			// Se não tem validação de senha no login o status não realiza o redirecionamnto conforme tempo expirado
    			
    			if($codUsuario){
    				
    				$rsSessao = self::getStatusSessao($idSession, $codUsuario);
    				
    				$statusOnline		= $rsSessao->fields["STATUS_ONLINE"];
    				$nrTempoExpiracao	= $rsSessao->fields["NR_TEMPO_EXPIRACAO"];
    				
    				$gerarIdSessao = 0;
    				$setIdStatusOff = 0;
    				
    				switch ($statusOnline){ 
    					case 1:
    						$status = 0;
    						// Para sessões armazenadas nos navegadores
    						if( $nrTempoExpiracao > TEMPO_EXPIRACAO_SESSAO ){
    							// Para essa situação expira quando se fecha a aba sem fazer logoff ou quando há problema de conexão de Internet
    							$status = 2;
    							$msgLoginStatus = "Sessão expirada! [2]";
    							unset($_SESSION[PROJECT_FOLDER]);
    							$gerarIdSessao = 1;
    							$setIdStatusOff = 1;
    						} else {
    							$sql = "update usuario set dt_online = ".date('YmdHis')."  , id_status = 1 where cd_usuario = $codUsuario";
    							//debugVar($sql,0,array(1,__FUNCTION__.' -- '.PROJECT_FOLDER));
    							self::ExecSql($sql);
    						}
    						break;
    					case 2:
    						// Está em outra sessão no mesmo navegador(fechou e abriu), ou em outro computador
    						$status = 2;
    						if("login"==$nav){
    							// Para essa situação o ID_STATUS não foi atualizado com logoff por fechar a aba ou navegador sem o logoff
    							// E quando se faz login novamente o ID_SESSION sempre será outro
    							// Não há retorno "status"
    							$security = new CoreWebSecurity();
    							$off = $codUsuario.";".date("YmdHi");
    							$off = $security->getIn($off);
    							$msgLoginStatus = "Você deixou seu usuário conectado no último acesso, ou em outro computador ou dispostivo.
    									<br /><strong><a href=\"#\" onclick=\"window.location.href='".CoreDefault::$CmsUrl."logoff/off/$off';\">Clique aqui para encerrar todas as conexões abertas</a></strong>";
    							unset($_SESSION[PROJECT_FOLDER]);
    						} else {
    							// Sessão expirada ou usuário está em outra sessão em qualquer navegador / computador
    							// Muito difícil de acontecer? Aqui o ID_STATUS está online mas o ID_SESSION está diferente
    							// Pode acontecer se houver um registro forçado de logon com outra sessão enquanto que se está logado ativamente em outra máquina
    							$msgLoginStatus = "Sessão expirada! [3]";
    							unset($_SESSION[PROJECT_FOLDER]);
    							$setIdStatusOff = 1;
    						}
    						break;
    					case 3:
    						// Para sessões armazenadas nos navegadores
    						// Para essa situação o ID_STATUS foi marcado corretamente com logoff e a sessão é outra quando se abre o navegador
    						// A situação também ocorre quando o usuário em navegador encerra o ID_STATUS 
    						$status = 3;
    						$msgLoginStatus = "A sessão foi desconectada.";
    						unset($_SESSION[PROJECT_FOLDER]);
    						$gerarIdSessao = 1;
    						break;
    					case 4:
    						// Situação ideal para nova sessão
    						$gerarIdSessao = 1;
    						break;
    					case 5:
    						if("login"!=$nav){
    							// Quando login se trata de nova sessão 
    							// Quando não login pode ser a mesma sessão - o usuário fechou a apenas a aba e expirou o tempo ou sem conexão de internet e depois voltou 
    							unset($_SESSION[PROJECT_FOLDER]);
    							$status = 1;
    							$gerarIdSessao = 1;
    							$setIdStatusOff = 1;
    							$msgLoginStatus = "Sessão expirada! [4]";						
    						} else {
    							// Considerado também como situação ideal para nova sessão
    							$status = 0;
    						}
    						break;
    					default:
    						// Alguma situação não prevista 
    						$msgLoginStatus = "Erro ao acessar sessão.";
    						unset($_SESSION[PROJECT_FOLDER]);
    						$status = 1;
    				}
    												
    				if($setIdStatusOff){
    					// No login o ID_STATUS não é atualizado porque é redirecionado direto para a tela de login
    					$sql = "update usuario set id_status = 0 where cd_usuario = $codUsuario";
    					self::ExecSql($sql);
    				}
    				
    			}else{
    				$status = 1;
    			}
    			
    			if($codUsuario && 0==$status){
    			    $notify = NotificacoesController::getVerificaNotificacao();
    			}
    		}
    		if($gerarIdSessao){
    			// Muda a idsession para a próxima tentiva de login
    			session_regenerate_id();
    		}
    		//debugVar("nav: $nav, DB: $idSessionDb, F: $idSession , P: ".session_id(),0,array(1,__function__ .' -- NAV -- ' . PROJECT_FOLDER ));
    		if($msgLoginStatus){
    			$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = $msgLoginStatus;
    		}
        }
        $arrayReturn["notify"] = $notify;
		$arrayReturn["status"] = $status;
		return $arrayReturn;
	}
		
	/**
	 * Verificação se a sessão deve estar com menus desativados
	 * @return boolean
	 */
	public static function getIsSessaoMenuDesativado(){
		// Situações em que os links não são ativados
		// - troca de senhão obrigatórias: primeiro acesso e forçado pelo administrador
		$result = false;
		if( (($_SESSION[PROJECT_FOLDER]["AUTH"]["CD_GRUPO"] == 48 ) && ((empty( $_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"]) )) || ($_SESSION[PROJECT_FOLDER]["AUTH"]["MUDAR_SENHA"] ))){
			$result = true;
		}
		return $result;
	}
	
	/**
	 * Atualiza o campo de motivo de bloqueio de usuário
	 * @param Int $codUsuario
	 * @param string $dsMotivoBloqu
	 */
	public static function doAlteraMsgBloqueio($codUsuario,$dsMotivoBloqu){
		$Usuario = new UsuarioModel();
		
		$Usuario->doClearFields();
		$Usuario->setCD_USUARIO($codUsuario);
		$Usuario->doFind();
		
		$Usuario->setDS_MOTIVO_BLOQUEIO($dsMotivoBloqu);
		$Usuario->doUpdate();
	}
	
	/**
	 * Ativa ou inativa um usuário
	 * @param Int $codUsuario
	 * @param boolean $idAtivo
	 */
	public static function doAtivaInativa($codUsuario , $idAtivo){
		$Usuario = new UsuarioModel();
		
		$Usuario->doClearFields();
		$Usuario->setCD_USUARIO($codUsuario);
		$Usuario->doFind();
		
		$Usuario->setID_ATIVO($idAtivo);
		$Usuario->doUpdate();
		
	}
	
	/**
	 * Verifica se o usuario em questão é um servidor, se for verifica se esta bloqueado, se sim, adiciona a data de desbloqueio.
	 * @param Int $codUsuario
	 * @param boolean $Ativo
	 */
	public function doDesbloqueiaServidor($codUsuario, $Ativo){
		
		$rsUsuario = $this->getUsuarioByCod($codUsuario);
		if($rsUsuario->getCD_GRUPO() == 48 && $Ativo && $rsUsuario->getDS_MOTIVO_BLOQUEIO() != '' ){
			$usuario = new UsuarioModel();
			$usuario->doClearFields();
			$usuario->setCD_USUARIO($codUsuario);
			$usuario->doFind();
			
			$usuario->setDT_DESBLOQUEIO(date('Ymdhi'));
			$usuario->doUpdate();
		}
	}
	
	private static function doDeleteMotivoDtDesbloqueio($codUsuario){
		$Usuario = new UsuarioModel();
		$Usuario->doClearFields();
		$Usuario->setCD_USUARIO($codUsuario);
		$Usuario->doFind();
		
		$Usuario->setDT_DESBLOQUEIO(0);
		$Usuario->setDS_MOTIVO_BLOQUEIO('');
		$Usuario->doUpdate();
		
	}
	
	/**
	 * Verifica se existe o nome de login
	 * @param string $dsLogin
	 * @return number
	 */
	public static function getHasDsLogin( $dsLogin ){
		$sql 		= "select cd_usuario from usuario where regexp_replace( ds_login, '".CHAR_IGNORADO_LOGIN."') = regexp_replace( '$dsLogin', '".CHAR_IGNORADO_LOGIN."')";
		$codUsuario = self::ExecSql($sql);
		return $codUsuario;
	}
	
	/**
	 * Atualiza o login do usuário verificando se o mesmo já está sendo utilizado por outro usuário
	 * @param array $post
	 * @return array
	 */
	public function doUpdateDsLogin( $post ){
		$result = array();
		$sessaoLogOn = $_SESSION[ PROJECT_FOLDER ]["AUTH"];
		//$codUsuario = $post["codUsuario"];
		$dsLogin 	= $post["dsUsuario"];
		
		$codGrupoSessao = $sessaoLogOn["CD_GRUPO"];
		
		if($codGrupoSessao){		    
			$codUsuario = $sessaoLogOn["CD_USUARIO"];
			if( $codUsuario && $dsLogin ){
				if( strlen( trim($dsLogin) ) >= 5 ){
					// Verifica o dsLogin já está sendo usado por usuário diferente do desejado  
					$sql 		= "select cd_usuario from usuario where regexp_replace( upper(ds_login), '".CHAR_IGNORADO_LOGIN."') = regexp_replace( upper('$dsLogin'), '".CHAR_IGNORADO_LOGIN."') and cd_usuario != $codUsuario";
					//debugVar( $sql , 0 , array(1,__CLASS__.'--'.__FUNCTION__));
					$rsUser 	= self::ExecSql($sql);
					if( $rsUser->_numOfRows > 0 && $rsUser->fields["CD_USUARIO"] ){
						$result["msg"] 	= "Este usuário já está sendo utilizado.";
						$result["ok"]	= false;
					} else {
					    $sql = "select count(1) qtd from usuario u where u.ds_login = '$dsLogin' ";
					    //debugVar( $sql , 0 , array(1,__CLASS__.'--'.__FUNCTION__));
					    $rs = self::ExecSql($sql);
					    if( $rs->fields['QTD'] > 0 ){
					        $result["msg"] = "Mantido o mesmo nome de usuário.";
					        $result["ok"]	= true;
					    } else{
					        // Executa alteração se não houver nada com o novo login
    						$usuario = new UsuarioModel();
    						$usuario->setCD_USUARIO( $codUsuario );
    						//O novo login
    						$usuario->setDS_LOGIN( $dsLogin );
    						$usuario->setAudit(true);
    						$usuario->doUpdate();
    						
    						$result["msg"] = "Usuário alterado com sucesso.";
    						$result["ok"]	= true;					        
					    }
					}
				} else {
					$result["msg"]	= "Mínimo de 5 caracteres.";
					$result["ok"] 	= false;
				}
			}			
		} else {
			$result["msg"]	= "É necessário estar logado.";
			$result["ok"] 	= false;
		}
		return $result;	
	}
	
	/**
	 * 
	 * @param string $dsLogin
	 * @param number $codUsuario
	 * @return array
	 */
	public static function getDsLoginDisponivel( $post ){
	    $html = $sql = '';
	    
	    $varPosts = explode(',', 'dsLogin,codUsuario,dsAltUser' );
	    foreach ( $varPosts as $var ){
	        ${"$var"} = isset($post[ $var ]) ? $post[ $var ] : null;
	    }
	    
	    if($dsAltUser){
	        //Essa variálve provém da tela de alteração de nome de usuário da própria pessoa no login
	        $codUsuario = $_SESSION[ PROJECT_FOLDER ]['AUTH']['CD_USUARIO'];
	        $dsLogin = $dsAltUser;
	    }
	    if($codUsuario){
	        $sql = " and cd_usuario != $codUsuario ";
	    }
	    
	    $success = false;
	    
 	    if($dsLogin){ 	        
     	    $sql = "select count(1) qtd_outro from usuario where regexp_replace( upper(ds_login), '".CHAR_IGNORADO_LOGIN."') = regexp_replace( upper('$dsLogin'), '".CHAR_IGNORADO_LOGIN."') $sql";
     	    //debugVar( $sql , 0 , array(1,__CLASS__.'--'.__FUNCTION__));
    	    $rs = self::ExecSql($sql,true);
    	    if( $rs->fields['QTD_OUTRO'] == 0 ){
    	        $success = true;
    	    } else {
    	        $html = '<span id="msg-login" class="userLogonOff">- Nome de usuário indisponível</span>';
    	    } 	        
 	    }
 	    $arrayReturn['html'] = $html;
 	    $arrayReturn['success'] = $success;
	    return $arrayReturn;	    
	}
	
	/**
	 * Realiza a verificação de primeiro acesso do servidor utilizando o Cartão ServFácil
	 * @param array $post
	 * @return array
	 */
	public static function doPrimeiroAcessoCartao($post){
		$result			= array();
		$refCartao 		= $post["REFERENCIA"];
		$post["login"]	= $refCartao;
		$rsPrimeiroLogin	= self::doVerificaPrimeiroLogin( $post );
		$primeiroAcesso		= $rsPrimeiroLogin["firstAccess"];
		if($primeiroAcesso){
			$indexToken 	= $post["POSICAO_CHAVE"];
			$token			= $post["CHAVE"];
			
			$result["msg"] 	= validaRefCartao( $refCartao );
			$result["tipoMsg"] = "alerta";
			
			if ( empty($result["msg"]) ) {
				$cartoes = new CartaoController();
				// Só testa a referencia se for passada
				$idCartao = $cartoes->getIdCartaoByRef( $refCartao );
				
				if(empty($idCartao)) {
					$result["msg"] .= "O cartão não foi encontrado!(3) <br />";
				} else {
					$cartao = new CartaoModel();
					$cartao->setID( $idCartao );
					$cartao->doFind();
					if ( $cartao->getSITUACAO_CARTAO() == "2" ) {
						$validate = false;
						$validate = $cartoes->doValidateCartao($indexToken, $idCartao, $token);
						if ( $validate ){
							$result["isValid"] = true;
							$result["msg"]   = "";
						} else {
							$result["msg"]   = "Chave de segurança incorreta(6).";
						}
					} else {
						$dominio = new DominioController();
						$result["msg"] = "O cartão está \"".$dominio->getDominioByCodChave("SITUACAO_CARTAO", $cartao->getSITUACAO_CARTAO() )->getDS_VALOR()."\"";
					}
				}
			}
			if ($result["isValid"]){
				$post['DS_LOGIN'] 	= $refCartao;
				$post['senha'] 		= $refCartao;
				self::doLogin($post);
			}
		} else {
			$_SESSION[PROJECT_FOLDER]["erro"]["login-primeiro-acesso"] = "Este não é o seu primeiro acesso. Volte para a tela anterior.";
		}
		return $result;
	}
	
	/**
	 * Função para alterar senha obrigatória e outras informações com validação de CPF e data de nascimento
	 * @param array $post
	 * @return array
	 */
	public static function doAlteracaoUsuarioAcesso($post){
		global $arraySituacaoTrocaSenhaObrig;
		$result   = array();
		$redirect = $dsLogin = '';
		
		$function 		= new CoreFunctions();
		$Pessoa 		= new PessoaController();
		$cod 			= $post['CD_USUARIO'];
		$RsUsuarioHome	= self::getUsuarioByCod( $cod );
		$dsEmail		= isset($post['DS_EMAIL']) ? $post['DS_EMAIL'] : null ;
		
		//$msg = $msgSenha = "";
		$msg = "";
		$result["tipoMsg"]	= "alerta";
		
		$RsPessoa  = $Pessoa->getPessoaByCod( $RsUsuarioHome->getCD_PESSOA() );
		
		// Valida o nível de segurança da senha caso algum dos campos de senha seja preenchido
		if( ( isset($post["DS_SENHA"]) && $post["DS_SENHA"]) || ( isset($post["DS_SENHA_CONF"]) && $post["DS_SENHA_CONF"] ) ) {
		    
		    $_post['ds_Senha']     = rtrim( $post["DS_SENHA"] );
		    $_post['ds_SenhaConf'] = rtrim( $post["DS_SENHA_CONF"] );
		    
		    $rsPassd = self::doVerificaSegurancaSenha($_post);
		    
		    if( !$rsPassd['secure'] ){
		        $msg .= $rsPassd['msg'];		        
		    }
		}
		
		if( isset($post['DS_LOGIN']) && $post['DS_LOGIN'] ){
		    
		    $dsLogin  = isset($post['DS_LOGIN']) ? trim($post['DS_LOGIN']) : '';
		    
		    $post['dsLogin']    = $dsLogin;
    		$post['codUsuario'] = $post['CD_USUARIO'];
    		$rsDsLogin = self::getDsLoginDisponivel($post);
    		if( !$rsDsLogin['success'] ){
    		    $msg .= '[- Nome de usuário indisponível]';
    		}		    
		}		
		
		if ( isset($post['ALT_USUARIO']) && in_array( $post['ALT_USUARIO'], $arraySituacaoTrocaSenhaObrig ) ) {
		    // Para chamadas de alteração de senha em sessão do próprio usuário com validação de outras informações e login na sequencia
		    
		    // Nesse caso a senha é troca de obrigatória
		    $CamposSenha = ( isset($post["DS_SENHA"]) && !empty($post["DS_SENHA"]) ? 1 : 0 ) + ( isset($post["DS_SENHA_CONF"]) && !empty($post["DS_SENHA_CONF"]) ? 1 : 0 );
		    if( 2 != $CamposSenha ){
		        // Valida o preenchimento dos dois campos
		        $msg .= "[- Preenchimento dos campos de senha são obrigatórios.]";
	        }
		    
			if( $RsPessoa->getNR_CPF()!= str_replace(array(".", "-"), "", $post["NR_CPF"]) ) {
				$msg .= "[- CPF incorreto!]";
			}
			
			if( $function->db2Date( $RsPessoa->getDT_NASCIMENTO(), 'd/m/Y') != $post["DT_NASCIMENTO"] ) {
				$msg .= "[- Data de nascimento inválida!]";
			}
			
			if($dsEmail && !validaEMAIL($dsEmail) ){
			    $msg .= "[- Endereço de e-mail inválido.]";
			}
			
    		if ( empty($msg) ) {
    		    
    			// Se for o primeiro acesso então o acesso inicial é a DT_ULTIMO_ACESSO para o próximo logon
    			if( empty( $_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"]) ){
    				$dtAcessoAtual = date("YmdHi");
    				$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_INICIO_ACESSO_ATUAL"] = $dtAcessoAtual;
    				$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_INICIO_ACESSO_ATUAL"] = $function->db2Date( $dtAcessoAtual , 'd/m/Y').' às '. $function->db2Date( $dtAcessoAtual , 'H:i:s');
    				$_SESSION[PROJECT_FOLDER]["AUTH"]["DT_ULTIMO_ACESSO"] 		= $function->db2Date( $dtAcessoAtual , 'd/m/Y').' às '. $function->db2Date( $dtAcessoAtual , 'H:i:s');
    				$post["DT_ULTIMO_ACESSO"]                       			= $dtAcessoAtual;
    			}
    			
    			// Se não passa nos testes anteriores não grava
    			if( self::doUpDateUsuario($post) ){
    			    // Depois de gravado
    				
    			    if( $dsEmail && trim($RsPessoa->getDS_EMAIL()) != trim( $dsEmail )){
    				    $pessoa = new PessoaModel();
    				    $pessoa->setCD_PESSOA( $RsPessoa->getCD_PESSOA() );
    				    $pessoa->setDS_EMAIL( trim($dsEmail) );
    				    //$pessoa->setDebug(true);
    				    $pessoa->doUpdate();
    				}
    				
    				// Realiza o login com o novo usuário e nova senha se for o caso
    				$RsUsuarioHome = self::getUsuarioByCod( $cod );
    				$post["DS_LOGIN"]	= $RsUsuarioHome->getDS_LOGIN();
    				$post["senha"]		= $post["DS_SENHA"];
    				
    				if ( in_array( $post['ALT_USUARIO'], $arraySituacaoTrocaSenhaObrig ) ) {
    				    // Realiza o login
    					self::doLogin($post);
    				}
    				
    				$result["tipoMsg"]	= "sucesso";
    				
    				$_SESSION[PROJECT_FOLDER]["AUTH"]["MUDAR_SENHA"] = 0;
    				
    				$sql = '';
    				if($post["DS_SENHA"]){
    				    $sql .= "{ CASE WHEN u.ds_senha = '".md5(trim($post["DS_SENHA"]))."' THEN '[Senha alterada com sucesso.]'  END }";
    				}
    				if($dsLogin){
    				    $sql .= "{ CASE WHEN u.ds_login = '$dsLogin' THEN '[Usuário alterado com sucesso.]' END }";
    				}
    				if($sql){
    				    $sql = str_replace(array('}{','{','}'), array('||','',''), $sql);
    				    $sql = "SELECT
                                    $sql msg
                                FROM
                                    usuario u
                                WHERE
                                    u.cd_usuario = {$post['CD_USUARIO']}";
                                    
                        $msg = self::ExecSql( $sql )->fields['MSG'];
    				} else {    				    
    				    $msg = "[Nada alterado!(2)]";
    				}
    				// No caso de sucesso se houve o redirecionamento ocorre para a home
    				// Para outros lugares deve ser implementado
    				$redirect = CoreDefault::$DocumentUrl;
    			} else {
    				$msg .="[-". $_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] . "]";
    			}
    		}
    		
		} else {
		    
		    $post['ID_ATIVO'] = ( !isset($post["ID_ATIVO"]) ) ? "0" : $post["ID_ATIVO"]; // Zero do formulário tem valor NULL/false
		    
		    if( !$msg ){		        
		        if( self::doUpDateUsuario($post) ){
		            //$msg = "[Senha alterada com sucesso!]";
		            
		            // Verificação do que foi alterado
		            $sql = '';
		            if($post["DS_SENHA"]){
		                $sql .= "{ CASE WHEN u.ds_senha = '".md5(trim($post["DS_SENHA"]))."' THEN '[Senha alterada com sucesso.]'  END }";
		            }
		            if($dsLogin){
		                $sql .= "{ CASE WHEN u.ds_login = '$dsLogin' THEN '[Usuário alterado com sucesso.]' END }";
		            }
		            if($sql){
		                $sql = str_replace(array('}{','{','}'), array('||','',''), $sql);
    		            $sql = "SELECT                                     
                                    $sql msg
                                FROM 
                                    usuario u
                                WHERE 
                                    u.cd_usuario = {$post['CD_USUARIO']}";
    		            
                        $msg = self::ExecSql( $sql )->fields['MSG'];
		            } else {
		                $msg = "[Nada alterado!(1)]";
		            }
		            
    		        // No caso de sucesso se houve o redirecionamento ocorre para a home
    		        // Para outros lugares deve ser implementado
    		        $redirect = CoreDefault::$DocumentUrl;
    		    } else {
    		        $msg .="[-". $_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] . "]";
    		    }
			}
		}
		
		if(!$redirect && $msg ){
		    $msg .= "[- Senha não alterada.]";
		}
		
		$result["redirect"] = $redirect; 
		$result["msg"] = $msg;
		
		return $result; 
	}
	
	/**
	 * Retorna a descrição de cadastro de grupo de usuário
	 * @param number $codGrupo
	 * @return  <NULL, <multitype, null>>
	 */
	public static function getGrupoUsuario( $codGrupo ){
		$result = null;
		if($codGrupo){
			$sql = "SELECT * FROM usuario_permissao_grupo where cd_grupo = $codGrupo";
			$result = self::ExecSql($sql);
		}
		return $result;
	}
		
	/**
	 * Retorna uma lista paginada de usuários
	 * @param array $post
	 */
	public function getHtmlListUsuariosPage($post){
		
        $codGrupoSessao	= $_SESSION[ PROJECT_FOLDER ]["AUTH"]["CD_GRUPO"];
		
		if(!isset($post["actionPage"])){
			$actionPage[ 2 ] = isset($post['totalPorPagina']) ? $post['totalPorPagina'] : 10 ;
			unset( $post['totalPorPagina'] );
			$post["actionPage"] = $actionPage;
		} else {
			$actionPage = $post["actionPage"];
		}
		
		doSetMaxExecutionZero();
		
		$post["typeSql"] = "totalByPage"; 
		
		$rsTotalPages = $this->getListUsuariosPage( $post );
		
		if($rsTotalPages->_numOfRows > 0 && $rsTotalPages->fields['TOTAL_PAGES'] > 0){			
			
            $actionPage[ 3 ] = $rsTotalPages->fields['TOTAL_PAGES'];
            			
			unset( $post["typeSql"] );
			$rsQuery = $this->getListUsuariosPage( $post );
            
			$columns = new StructureJsonQuery();
			
			//--Bloco de dados para demonstração dos dados no sistema.
			$columns->addColumn("USUARIO_DS_LOGIN", 	"Usuário",				"string",  	"alfa");
			$columns->addColumn("USUARIO_PESSOA",  		"Pessoa",  				"string",  	"alfa");
			$columns->addColumn("NR_CPF",  				"CPF",  				"string",  	"alfa");
			$columns->addColumn("DS_LOCAL",  			"Local",  				"string",  	"alfa");
			$columns->addColumn("DS_GRUPO_PERFIL",  	"Grupo de Permissões",	"string",	"alfa");
			$columns->addColumn("DT_ULTIMO_ACESSO", 	"Último Acesso",		"datetime",	"dat");
			if(1==$codGrupoSessao){
				$columns->addColumn("DS_IP", 	"IP",		"string",	"dat");
				$columns->addColumn("DS_ULTIMA_ACAO", 		"Última função utilizada", 				"string",	"alfa");
			}
			$columns->addColumn("ID_ATIVO", 			"Ativo",  				"string",	"dat");
			$columns->addColumn("ID_STATUS", 			"Status", 				"string",	"status");
			
			$arrayColumns = $columns->getArrayColumns();
			
			$rsResumoGeralUsuarios = self::ExecSql( $this->getParserSqlUsuarios( $post, "totalGeral") );
			
			if($rsResumoGeralUsuarios->_numOfRows > 0){
				$tableResumoUsuarioOnline = new TableColumns();
				$tableResumoUsuarioOnline->addColumn("<strong>Situação</strong>", "DS_STATUS", "string", "dat", "","class='text-center'");
				$tableResumoUsuarioOnline->addColumn("", "STATUS_FIGURA", "string", "status", "");
				$tableResumoUsuarioOnline->addColumn("<strong>Qtd</strong>", "QTD_GERAL", "num", "num", "","class='text-right'");
				$tableResumoUsuarioOnline->addColumnTotalBy("QTD_GERAL");
				$tableResumoUsuarioOnline->setCaption("Resumo geral dos registros encontrados", "bg_azul", "text-center");
				$tableResumoUsuarioOnline->doParserColumns($rsResumoGeralUsuarios);
				$array["resumoUsuarioOnline"] = $tableResumoUsuarioOnline->getHtmlColumnsReturn();
			}
			
			$tables_FieldsNamePrimaryKey = array();
			$tables_FieldsNamePrimaryKey["table"]		  = "usuario";
			$tables_FieldsNamePrimaryKey["primaryKeys"]   = "CD_USUARIO";
			$tables_FieldsNamePrimaryKey["fieldsBoolean"] = "ID_ATIVO";
			
			$arrayReturn = getStructureDataGroupBy($rsQuery, $arrayColumns,null,null,$tables_FieldsNamePrimaryKey,null,null,null,null,$actionPage,null,null,$array);
			// Não precisa dessa chave
			unset($arrayReturn["TITULOS_COLUNAS"]);
			
		} else {
			$arrayReturn["msg"]		= "Nenhum Registro Encontrado.";
			$arrayReturn["classe"]	= "alerta";
		}
		$arrayReturn["tpMsg"] = 2;
		$arrayReturn["idMsg"] = "resposta_fixed";
		doSetMaxExecutionDefault();
		return $arrayReturn;
	}
	
	/**
	 * Finaliza sessões de usuários que ainda esteja ativas no banco
	 * @param array $post
	 */
	public function doFinishSession($post){
		$result["msg"] = "";
		$param = $post['param'];
		$param = str_replace("uts_", "", $param);
		$codUsuario = hex2bin($param);
		$sql = "update usuario set id_status = 0 where cd_usuario = $codUsuario";
		self::ExecSql($sql);
		$result["html"] = "<span class=\"user_off\"></span>";
		return $result;
	}
	
	/**
	 * Retorna informações do usuário para exibir no painel de acesso pelo número de CPF
	 * @param array $post
	 * @return array
	 */
	public static function doLoginCpf($post){
		$arrayDsLogin = array();
		$Usuario = new UsuarioController();
		
		$nrCpf = $post["nrCpf"];
		// Acesso por certificado digital
		$sql = "select 
					usu.cd_pessoa,
					usu.cd_usuario,
					usu.cd_grupo,
					usu.ds_login||' ('||grp.nm_grupo||')' ds_login
					,decode(usu.cd_grupo, 1,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												--,48,(select pes.nm_pessoa    from pessoa    pes, servidor ser where pes.cd_pessoa = ser.cd_pessoa and ser.cd_servidor = usu.cd_servidor)
												,48,(select distinct pes.nm_pessoa    from pessoa    pes, servidor ser , usuario_acesso usa where pes.cd_pessoa = ser.cd_pessoa and ser.cd_servidor = usa.cd_servidor and usa.cd_usuario = usu.cd_usuario )
												,61,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												,62,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												,63,(select cor.nm_correspondente    from correspondente cor where cor.cd_correspondente = usu.cd_correspondente)
												,64,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												,65,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												,67,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												,66,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												,68,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												,69,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
												) nm_pessoa
					,decode(usu.cd_grupo, 1,null
												,48,(select ccc.nm_fantasia||' / '||aaa.nm_averbador  from pessoa    pes, servidor ser, consignante ccc, averbador aaa where aaa.cd_consignante_master = ser.cd_consignante_master and aaa.cd_consignante = ser.cd_consignante and aaa.cd_averbador = ser.cd_averbador and ccc.cd_consignante_master = ser.cd_consignante_master and ccc.cd_consignante = ser.cd_consignante and pes.cd_pessoa = ser.cd_pessoa and ser.cd_servidor = usu.cd_servidor)
												,61,(select con.nm_fantasia||' ('||dom.ds_valor||')'    from consignataria    con , dominio dom where dom.ds_tipo_dominio = 'ID_TIPO_CONSIGNATARIA' and dom.ds_chave = con.tp_consignataria and con.cd_consignataria = usu.cd_consignataria)
												,62,(select con.nm_fantasia||' / '||fil.nm_filial   from consignataria con, consignataria_filial    fil where fil.cd_consignataria = con.cd_consignataria and fil.cd_consignataria = usu.cd_consignataria and fil.cd_filial = usu.cd_filial)
												,67,(select pdv.nm_pdv||' / '||fil.nm_filial   from pdv pdv, consignataria_filial    fil where fil.cd_consignataria = pdv.cd_consignataria and fil.cd_consignataria = usu.cd_consignataria and fil.cd_filial = usu.cd_filial and pdv.cd_pdv = usu.cd_pdv)
												,63,(select crpm.nm_correspondente from correspondente crpm where crpm.cd_correspondente_master = 0 and crpm.cd_correspondente = usu.cd_correspondente )
												,64,(select sig.nm_fantasia    from consignante sig where  usu.cd_consignante_master = sig.cd_consignante_master and usu.cd_consignante = sig.cd_consignante)
												,65,(select cns.nm_fantasia||' / '||ave.nm_averbador from consignante cns, averbador ave where ave.cd_consignante_master = cns.cd_consignante_master and ave.cd_consignante = cns.cd_consignante and ave.cd_consignante_master = usu.cd_consignante_master and ave.cd_consignante = usu.cd_consignante and ave.cd_averbador = usu.cd_averbador)
												,66,(select crpm.nm_correspondente||' / '||crpf.nm_correspondente from correspondente crpm, correspondente crpf where crpm.cd_correspondente = crpf.cd_correspondente_master and crpf.cd_correspondente = usu.cd_correspondente)
												,68,(select ag.nm_agencia from agencia ag where ag.cd_agencia_master = 0 and ag.cd_agencia = usu.cd_agencia)
												,69,(select ag.nm_agencia from agencia ag where ag.cd_agencia = usu.cd_agencia)
												) ds_local
												
				from
					usuario	usu
					,usuario_permissao_grupo grp
					,pessoa pp 
				where
					pp.nr_cpf = '$nrCpf'
				and grp.cd_grupo = usu.cd_grupo
				AND usu.cd_pessoa = pp.cd_pessoa";
		//debugVar("--line:".__LINE__.":\n$sql\n;", 0, array( 1, __CLASS__ . '--' . __FUNCTION__));
		$rs = self::ExecSql($sql);
		
		$i = 0;
		
		if($rs->_numOfRows > 0){
		    
		    $cert = isset($post["cert"]) ? $post["cert"] : null;
			
			while(!$rs->EOF){
									
				$arrayUsuario = array();
				
				$arrayUsuario["codUsuario"]	= $rs->fields["CD_USUARIO"];
				$codUsuario					= $rs->fields["CD_USUARIO"];
				
				switch ( $rs->fields["CD_GRUPO"] ){
					case "1":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "48":
						
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						
						//???????? e se uma pessoa estiver em duas prefeituras?
						//$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$usuarioMatricula	= UsuarioController::getUsuarioMatServidor($codUsuario);
						if($usuarioMatricula->_numOfRows > 0){
							foreach($usuarioMatricula as $key => $value){
								$i++;
								
								$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
								$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"]." Nr. Matº: ".$value["MATRICULA"]." / ".$value["AVERBADOR"]." / ".$value["LOTACAO"]." / ".$value["CATEGORIA"];
								$arrayUsuario["cert"]		= $cert;
								
								$arrayUsuario["nrMatricula"]	= $value["MATRICULA"];
								$arrayUsuario["codServidor"]	= $value["CD_SERVIDOR"];
								$arrayUsuario["codAverbador"]	= $value["CD_AVERBADOR"];
								$arrayUsuario["nmAverbador"]	= $value["AVERBADOR"];
								$arrayUsuario["dsLotacao"]		= $value["LOTACAO"];
								$arrayUsuario["dsCategoria"]	= $value["CATEGORIA"];
								$arrayUsuario["codConsignanteMaster"]	= $value["CD_CONSIGNANTE_MASTER"];
								$arrayUsuario["codConsignante"]			= $value["CD_CONSIGNANTE"];							
								
								$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;							
							}							
						}
						break;
					case "61":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "62":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "63":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "64":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "65":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "66":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "67":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "68":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "69":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;
					case "201":
						
						$i++;
						$arrayUsuario["codGrupo"]	= $rs->fields["CD_GRUPO"];
						$arrayUsuario["dsLogin"]	= $rs->fields["DS_LOGIN"];
						$arrayUsuario["nmPessoa"]	= $rs->fields["NM_PESSOA"];
						$arrayUsuario["dsLocal"]	= $rs->fields["DS_LOCAL"];
						$arrayUsuario["cert"]		= $cert;
						$arrayDsLogin[ $rs->fields["CD_PESSOA"] ][ "ACESSO" ][ $i ] = $arrayUsuario;
						
						break;						
				}
				
				$rs->MoveNext();
			}
			if($i > 1){
				$_SESSION[ PROJECT_FOLDER ]["AUTH"]["ESCOLHER"] = 1;
			}
		} else {
			$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "Usuário não encontrado pelo e-CPF!";
		}
		
		if($i>0){
			//Se encontrar apenas um login, realiza o acesso
			//Caso contrário exibir tela para decidir
			$_SESSION[PROJECT_FOLDER]["AUTH"]["CPF"]		= 1;
			$_SESSION[PROJECT_FOLDER]["AUTH"]["USUARIOS"]	= $arrayDsLogin;
			if( !isset($_SESSION[PROJECT_FOLDER]["AUTH"]["ESCOLHER"]) ){
				//Senão for necessário escolher já realiza o login no usuário
				$post["login-cpf"] = 1;
				self::doSetLoginCpf($post);
			}			
		}else{
			unset($_SESSION[PROJECT_FOLDER]["AUTH"]);
			$_SESSION[PROJECT_FOLDER]["erro"]["msgLogin"] = "Usuário não encontrado!(2)";
		}
		return $arrayDsLogin;
	}
	
	/**
	 * Retorna informações do usuário para exibir no painel de acesso 
	 * @param array $codUsuario
	 */
	public static function getInfoLoginSessao($codUsuario){
		if($codUsuario){
			$sql = "select 
						usu.cd_usuario, usu.ds_login||' ('||grp.nm_grupo||')' ds_login
						
						,decode(usu.cd_grupo, 1,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										--,48,(select pes.nm_pessoa    from pessoa    pes, servidor ser where pes.cd_pessoa = ser.cd_pessoa and ser.cd_servidor = usu.cd_servidor)
										,48,(select distinct pes.nm_pessoa    from pessoa    pes, servidor ser , usuario_acesso usa where pes.cd_pessoa = ser.cd_pessoa and ser.cd_servidor = usa.cd_servidor and USA.CD_USUARIO = usu.cd_usuario)
										,61,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										,62,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										,63,(select cor.nm_correspondente    from correspondente cor where cor.cd_correspondente = usu.cd_correspondente)
										,64,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										,65,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										,67,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										,66,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										,68,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										,69,(select pes.nm_pessoa    from pessoa    pes where pes.cd_pessoa = usu.cd_pessoa)
										) nm_pessoa
						
						,decode(usu.cd_grupo, 1,null
										,48,(select ccc.nm_fantasia||' / '||aaa.nm_averbador  from pessoa    pes, servidor ser, consignante ccc, averbador aaa where aaa.cd_consignante_master = ser.cd_consignante_master and aaa.cd_consignante = ser.cd_consignante and aaa.cd_averbador = ser.cd_averbador and ccc.cd_consignante_master = ser.cd_consignante_master and ccc.cd_consignante = ser.cd_consignante and pes.cd_pessoa = ser.cd_pessoa and ser.cd_servidor = usu.cd_servidor)
										,61,(select con.nm_fantasia||' ('||dom.ds_valor||')'    from consignataria    con , dominio dom where dom.ds_tipo_dominio = 'ID_TIPO_CONSIGNATARIA' and dom.ds_chave = con.tp_consignataria and con.cd_consignataria = usu.cd_consignataria)
										,62,(select con.nm_fantasia||' / '||fil.nm_filial   from consignataria con, consignataria_filial    fil where fil.cd_consignataria = con.cd_consignataria and fil.cd_consignataria = usu.cd_consignataria and fil.cd_filial = usu.cd_filial)
										,67,(select pdv.nm_pdv||' / '||fil.nm_filial   from pdv pdv, consignataria_filial    fil where fil.cd_consignataria = pdv.cd_consignataria and fil.cd_consignataria = usu.cd_consignataria and fil.cd_filial = usu.cd_filial and pdv.cd_pdv = usu.cd_pdv)
										,63,(select crpm.nm_correspondente from correspondente crpm where crpm.cd_correspondente_master = 0 and crpm.cd_correspondente = usu.cd_correspondente )
										,64,(select sig.nm_fantasia    from consignante sig where  usu.cd_consignante_master = sig.cd_consignante_master and usu.cd_consignante = sig.cd_consignante)
										,65,(select cns.nm_fantasia||' / '||ave.nm_averbador from consignante cns, averbador ave where ave.cd_consignante_master = cns.cd_consignante_master and ave.cd_consignante = cns.cd_consignante and ave.cd_consignante_master = usu.cd_consignante_master and ave.cd_consignante = usu.cd_consignante and ave.cd_averbador = usu.cd_averbador)
										,66,(select crpm.nm_correspondente||' / '||crpf.nm_correspondente from correspondente crpm, correspondente crpf where crpm.cd_correspondente = crpf.cd_correspondente_master and crpf.cd_correspondente = usu.cd_correspondente)
										,68,(select ag.nm_agencia from agencia ag where ag.cd_agencia_master = 0 and ag.cd_agencia = usu.cd_agencia)
										,69,(select ag.nm_agencia from agencia ag where ag.cd_agencia = usu.cd_agencia)
										) ds_local
					from
						usuario usu
						,usuario_permissao_grupo grp				
					where
						grp.cd_grupo   = usu.cd_grupo
					and usu.cd_usuario = $codUsuario ";
			//debugVar($sql,0,array(1,__class__.'--'.__function__));
			$rsInfoLogin = self::ExecSql($sql);
		}
		return $rsInfoLogin;
	}
	
	/**
	 * Realiza o registro de sessão para acesso por CPF
	 * @param array $post
	 */
	public static function doSetLoginCpf($post){
		$loginCpf = $post["login-cpf"];
		if($loginCpf!="reset" && is_numeric($loginCpf)){			
			if( sizeof($_SESSION[ PROJECT_FOLDER ]["AUTH"]["USUARIOS"]) == 1 ){
				// O login é definido apenas pelo índice
				foreach ( $_SESSION[ PROJECT_FOLDER ]["AUTH"]["USUARIOS"] as $codPessoa => $arrayAcessos ){
					$acesso = $arrayAcessos["ACESSO"][ $post["login-cpf"] ];			
				}
				// Campos para serem mantidos na troca de usuário
				$fields = array("AUDIT_SESSAO_USUARIO","ESCOLHER","CPF","USUARIOS","MSG_GLOBAL_SYS");
				foreach ($_SESSION[PROJECT_FOLDER]["AUTH"] as $field => $value){
					if( !in_array( $field, $fields ) ){
						unset($_SESSION[PROJECT_FOLDER]["AUTH"][ $field ]);
					}
				}
				$codUsuario		= $acesso["codUsuario"];
				$usuarioSessao = new UsuarioModel();
				$usuarioSessao->setCD_USUARIO( $codUsuario );
				$usuarioSessao->doFind("CD_USUARIO");
				self::setSessionLogin($usuarioSessao, $acesso);
			} else {
				self::unsetSessionLogin();
			}
		} elseif($loginCpf=="reset") {
			// faz um reset limpando a sessão
			self::unsetSessionLogin();
		}
	}
	
	/**
	 * Retorna uma lista de matrículas para acesso como servidor. 
	 * Acessos ativos com ou sem contratos ativos. 
	 * Acessos inativos apenas com contratos ativos.
	 * @param array $codUsuario
	 */
	public static function getUsuarioMatServidor($codUsuario){
		$rsUsuario = array();
        		
		if($codUsuario){
        
            $where_sql = "";
            
			$restricaoIp = new RestricaoIpController();
			
			$strCodServidorBloqueado = $restricaoIp->doValidaMatricula($codUsuario,$_SERVER["REMOTE_ADDR"]);
			
			if($strCodServidorBloqueado != null){
				$where_sql = "AND UA.CD_SERVIDOR not in ({$strCodServidorBloqueado})";
			}
			$sql = "SELECT 
					    sv.nm_servidor  nm_pessoa
						,sv.nr_matricula matricula
						,sv.cd_servidor cd_servidor
						,sv.nm_averbador averbador
						,sv.ds_lotacao lotacao
						,sv.cd_averbador cd_averbador
						,sv.ds_categoria_consignante  categoria
						,ua.cd_servidor
						,sv.cd_consignante_master
						,sv.cd_consignante					
					FROM 
					    usuario us, 
					    usuario_acesso ua,
					    v_servidor sv  
					WHERE 
					    us.cd_usuario = $codUsuario
					and ua.cd_usuario = us.cd_usuario 
					AND ua.cd_grupo = 48
					AND sv.cd_servidor = ua.cd_servidor
					$where_sql
					AND ((sv.id_ativo = 1) OR (sv.id_ativo = 0 AND EXISTS ( SELECT * FROM solicitacao_consignacao sc WHERE sc.cd_servidor = sv.cd_servidor AND sc.id_situacao_solicitacao != 800)))";
			//debugVar("--line: ".__LINE__."\n$sql\n;",0,array("1",__CLASS__.'--'.__FUNCTION__));			
			$rsUsuario = self::ExecSql($sql);
			return ($rsUsuario->_numOfRows > 0) ? $rsUsuario : array(); 			
		}
		return $rsUsuario;
	}

	/**
	 * Retorna uma lista de grupos conforme o tipo de permissao de acesso com PERFIL ou SEM PERFIL
	 * @param number $codTipoUsuario
	 */
	public static function getListTipoUsuarioPermissao($post){
		$codTipoUsuario = $post["codTipoUsuario"];
		switch ($codTipoUsuario){
			case 1:
				$rsQuery = PermissaoPerfilController::getGrupoPermissaoSemPerfil();
				break;
			case 2:
				$rsQuery = PermissaoPerfilController::getGrupoPermissaoPerfil();
				break;
			default:
				//todos
				$sql = "select
							*
						from
							usuario_permissao_grupo
						order by
							upper(nm_grupo)";
				$rsQuery = self::ExecSql($sql);
		}
		return getStructureOptionData($rsQuery, "CD_GRUPO", "NM_GRUPO");
	}
	
	/**
	 * Retorna uma listagem de seleção conforme o grupo desejado
	 * @param array $post
	 * @return array
	 */
	public static function getHtmlItemGruposPermissoesUsuarios($post){
		$arrayReturn = array();
		$codGrupo = $post["codGrupo"];
		$usuario = new UsuarioController();
		$rsItens = $usuario->getItensGrupo($codGrupo);
		$arrayReturn["option"] = "<option value='' > Selecione ".$rsItens["NM_ITEM"]." </option>";
		if(isset($rsItens["ITENS"]) && $rsItens["ITENS"]){
			$arrayReturn["count"]  = sizeof($rsItens["ITENS"]);
			foreach ( $rsItens["ITENS"] as $item => $value ){
				$arrayReturn["option"] .= "<option value='".$value["CD_ITEM_GRUPO"]."' >". $value["DS_ITEM"]. " </option>";
	 
			}
		}			
		return $arrayReturn;
	}
    
    /**
	 * Função para registro de usuário a partir do $_POST
	 * @param array $post
	 * @return boolean
	 */
    public static function doRegistraUsuario($post){
        $msg = '';
	    $ok = true;
	    
	    if ($post["CD_GRUPO"] == 48){
	        $codPessoa		= $post["CD_PESSOA"];
	        if(!$codPessoa){
	            $msg = " A pessoa não foi escolhida! ";
	            $ok = false;
	        } else {
	            $servidor = new ServidorModel();
	            $servidor->setCD_SERVIDOR();
	            $servidor->setCD_PESSOA( $codPessoa );
	            $servidor->doFind();
	            
	            if	( $servidor->getCount() <=  0 ){
	                $msg = "Essa pessoa não está cadastrada como Servidor no sistema!";
	                $ok = false;
	            } else {
	                // Então a primeiro cadastro como servidor fica registrado
	                $post["CD_SERVIDOR"] = $servidor->getCD_SERVIDOR();
	                
	                $usuarioCadastrado = new UsuarioModel();
	                $usuarioCadastrado->doClearFields();
	                $usuarioCadastrado->setCD_PESSOA( $codPessoa );
	                $usuarioCadastrado->setCD_GRUPO( 48 );
	                $usuarioCadastrado->doFind();
	                $codUsuario = $usuarioCadastrado->getCD_USUARIO();
	                if ( $codUsuario ){
	                    $msg = "Esta pessoa já possui um usuário cadastrado!";
	                    $ok = false;
	                } else {
	                    
	                    //Verificação se a senha possui os dados necessarios...
	                    //Letras e numeros Minimo de 8 caracteres, maximo de 12
	                    
	                    if (preg_match('/[A-Za-z]/', $post['DS_SENHA']) && preg_match('/[0-9]/', $post['DS_SENHA']) && ( strlen($post['DS_SENHA'] >= 8) && ( strlen($post['DS_SENHA'] <= 12) ) ) ){
	                        
	                        $UsuarioCtl = new UsuarioController();
	                        $UsuarioCtl->doInsertUsuario($post);
	                        
	                        // Procura o login que foi cadastrado para perfil servidor para achar o código de usuário
	                        $post["CD_USUARIO"] = $UsuarioCtl->getUsuarioServidorByCodPessoa( $post["CD_PESSOA"] )->fields["CD_USUARIO"];
	                        
	                        UsuarioAcessoController::doRegistraUsuarioAcessoServidor($codPessoa);
	                    }
	                }
	            }
	        }
	    } else {
        
	        $msg_error		= "";
	        $codTipoUsuario	= $post["CD_TIPO_USUARIO"];
	        if( $codTipoUsuario == 2){
	            // Usuário com perfil
	            $codPerfil = $post["CD_PERFIL"];
	            if(!$codPerfil){
	                $msg_error = "O perfil não foi selecionado para esse tipo de usuário! ";	                
	            }
	        } else {
	            // Usuário a partir do grupo
	        }
	        
	        if(!$msg_error){
	            
	            $codPessoa = $post["CD_PESSOA"];
	            if(!$codPessoa){
	                $msg = "A pessoa não foi escolhida!";
	                $ok = false;
	            } else {
	                $usuarioCadastrado = new UsuarioModel();
	                $usuarioCadastrado->doClearFields();
	                $usuarioCadastrado->setCD_PESSOA( $codPessoa );
	                $usuarioCadastrado->setCD_GRUPO( $post["CD_GRUPO"] );
	                switch ($post["CD_GRUPO"]){
	                    case 61 :
	                        //Consignataria
	                        $usuarioCadastrado->setCD_CONSIGNATARIA($post["CD_CONSIGNATARIA"]);
	                        break;
	                    case 62 :
	                        //Filial Consignataria
	                        $usuarioCadastrado->setCD_FILIAL($post["CD_FILIAL"]);
	                        break;
	                    case 63 :
	                        //Correspondente
	                        $usuarioCadastrado->setCD_CORRESPONDENTE($post["CD_CORRESPONDENTE_MASTER"]);
	                        break;
	                    case 66 :
	                        //Filial Correspondente
	                        $usuarioCadastrado->setCD_CORRESPONDENTE($post["CD_CORRESPONDENTE_FILIAL"]);
	                        break;
	                    case 64 :
	                        //Consignante
	                        $usuarioCadastrado->setCD_CONSIGNANTE($post["CD_CONSIGNANTE"]);
	                        break;
	                    case 65 :
	                        //Averbador
	                        $codServidor = $post["CD_SERVIDOR"];
	                        $rsServAverb = ServidorController::getValueField($codServidor, 'CD_AVERBADOR,CD_CONSIGNANTE,CD_CONSIGNANTE_MASTER');
	                        $aaa = explode(',','CD_AVERBADOR,CD_CONSIGNANTE,CD_CONSIGNANTE_MASTER');
	                        foreach ($aaa as $_field_n) {
    	                        $post["$_field_n"] = $rsServAverb["$_field_n"];	                            
	                        }
	                        $usuarioCadastrado->setCD_AVERBADOR($post["CD_AVERBADOR"]);
	                        break;
	                    case 68 :
	                        //Agência Master
	                        $usuarioCadastrado->setCD_AGENCIA($post["CD_AGENCIA"]);
	                        break;
	                    case 69 :
	                        //Agência Filial
	                        $usuarioCadastrado->setCD_AGENCIA($post["CD_AGENCIA_FILIAL"]);
	                        break;
	                }
					                    
	                //$usuarioCadastrado->setDebug(true);
	                $usuarioCadastrado->doFind();
	                
	                $codUsuario = $usuarioCadastrado->getCD_USUARIO();
	                
	                if ( $codUsuario ){																																	 
	                    $msg = "Esta pessoa já possui um usuário cadastrado para essa instituição!";
	                    $ok = false;
	                } else {
	                    
	                    // Verifica se o login está sendo utilizado
	                    $post['dsLogin'] = $post['DS_LOGIN'];
	                    
	                    $rsDsLogin = self::getDsLoginDisponivel($post);
	                    
	                    if( !$rsDsLogin['success'] ){
	                        $msg .= '[- Nome de usuário indisponível]';
	                        $ok = false;
	                    }
	                    
	                    if(!$msg){
	                        
    	                    $_post['ds_Senha']     = rtrim( $post["DS_SENHA"] );
    	                    $_post['ds_SenhaConf'] = rtrim( $post["DS_SENHA_CONF"] );
    	                    
    	                    $rsPassd = self::doVerificaSegurancaSenha($_post);
    	                    
    	                    if($rsPassd['secure']){
    	                        $UsuarioCtl = new UsuarioController();
    	                        $rsInsertUsuario = $UsuarioCtl->doInsertUsuario($post);
    	                        if( $rsInsertUsuario["success"] ){
    	                            $msg = "Usuário cadastrado com sucesso.";
    	                        } else {
    	                            $msg = $rsInsertUsuario["msg"];
    	                            $ok = false;
    	                        }
    	                    } else {
    	                        $msg = $rsPassd['msg'];
    	                        $ok = false;
    	                    }
	                    }
	                }
	            }
	        } else {
	            $msg   = $msg_error;
	            $ok = false;
	        }
	    }
	    if($ok){
	        //$Function->doRedirect($Function->getParentUrl("insert","list").$Function->getParameterPage());
	        //exit;
	    }
	    $arrayReturn["ok"]     = $ok;
	    $arrayReturn["msg"]    = $msg;
	    return $arrayReturn;
	}
	
	/**
	 * 
	 * @param array $post
	 * @return array
	 */
	public static function getAutoCompletePessoaUsuario($post){
		$pessoa = new PessoaController();
		
		$post["CD_GRUPO"]		= isset( $post["codGrupo"]     ) ? $post["codGrupo"] : null; 
		$post["CD_ITEM_GRUPO"]	= isset( $post["codItemGrupo"] ) ? $post["codItemGrupo"] : null;
		$post["CD_PERMISSAO"]	= isset( $post["codPermissao"] ) ? $post["codPermissao"] : null;
		
		$arrayReturn = $pessoa->getListPageByNomePessoa($post);
		
		return $arrayReturn;		
	}
	
	/**
	 * Retorna um array de colunas ou o valor de determinada coluna do registro de usuário conforme o(s) nome de colunas de parâmetro
	 * @param number $codUsuario
	 * @param string $fieldName
	 */
	public static function getValueField( $codUsuario, $fieldName ){
		if( !isEmptyVars( array( $codUsuario, $fieldName ) ) ){
			$sql = " select $fieldName from usuario where cd_usuario = $codUsuario";
			if( strpos($fieldName, ",") > 0 ){
				return self::ExecSql( $sql )->fields;
			} else {
				return self::ExecSql( $sql )->fields[ strtoupper( $fieldName ) ];
			}
		}
		return null;
	}
	
	/**
	 * 
	 * @param array $post
	 * @return string[]
	 */
	public static function getListUserNrCpfDtNasc($post){
	    
	    $nrCpf     = $post["nrCpf"];
	    $dtNasc    = $post["dtNasc"];
	    
	    $arrayReturn = array();
	    
	    $arrayReturn["userPwsRec"]    = "";
	    
	    $PessoaController  = new PessoaController();
	    $function          = new CoreFunctions();
	    $dtNasc = $function->date2Db($dtNasc);
	    
	    $rsPessoa = $PessoaController->getPessoaByCPF( $nrCpf );
	    
	    if($rsPessoa->getDT_NASCIMENTO() == $dtNasc){
	        
	        $codPessoa = $rsPessoa->getCD_PESSOA();
	        
	        $sql = "select u.cd_usuario, u.ds_login from usuario u where u.cd_pessoa = $codPessoa order by upper(u.ds_login)";
	        
	        $rsUsuario =  self::ExecSql($sql);
	        
	        if( $rsUsuario->_numOfRows == 1 ){
	            
	            $arrayReturn["userPwsRec"]    = $rsUsuario->fields["CD_USUARIO"];
	            
	        } else if($rsUsuario->_numOfRows > 1){
	                
	            $html = "";
	            
	            while(!$rsUsuario->EOF){	                
	                $html .= "<input type='radio' name='radioCdUsuario' value='{$rsUsuario->fields["CD_USUARIO"]}' id='rec-user-{$rsUsuario->fields["CD_USUARIO"]}'> <label for='rec-user-{$rsUsuario->fields["CD_USUARIO"]}'>{$rsUsuario->fields["DS_LOGIN"]}</label><br />";
	                $rsUsuario->MoveNext();
	            } 	        
	            
    	        $data["idModal"]    = "user-cad";
    	        $data["title"]      = "Selecione o usuário para recuperação de senha:";
    	        $data["fadeIn"]     = 1000;
    	        $data["content"]    = $html;
    	        
    	        $arrayReturn["listUserCpf"] = $data;	            
	        }
	    }
	    
	    return $arrayReturn;	    
	}
	
	/**
	 * Verifica a segurança da senha criada
	 * @param array $post
	 * @return string
	 */
	private static function doVerificaSegurancaSenha($post){
	    $msg = '';
	    
	    $varPosts = explode(',','ds_Senha,ds_SenhaConf');
	    	    
	    foreach ($varPosts as $var){
	        ${"$var"} = isset( $post[ $var ] ) ? $post[ $var ] : null;
	    }
	    
	    if( $ds_Senha != $ds_SenhaConf ){
	        $msg .= "[- As senhas não conferem.]";
	    } else {
	        if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $ds_Senha )){
	            $msg .= "[A senha deve ser composta de letras e números.]";
	        }
	        if (strlen( $ds_Senha ) < 8 || ( strlen( $ds_Senha ) > 12 ) ){
	            $msg .= "[O senha deve ter um minimo de 8 e maximo de 12 caracteres.]";	             
	        }
	    }
	    
	    $pwdSecure = ($msg) ? false : true;
	    $arrayReturn['msg']    = $msg;
	    $arrayReturn['secure'] = $pwdSecure;
	    return $arrayReturn;
	}
}
