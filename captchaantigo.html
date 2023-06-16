<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
</head>

<body>
	<form>
		<!-- Captcha -->
		<div class="col-xs-12" style="padding-bottom: 30px;" onload="generate()">
			<div style="display: flex; justify-content: center; align-items: center;">
				<div class="rounded-border" style="background-color: #ced0d1; padding: 15px; display: flex; flex-direction: column;">
					<div style="text-align: left;">
						<p style="color:#1384AD; text-align: left;">Digite as letras informadas</p>
						<div style="display: flex; align-items: center;">
							<img id="captchaImg" src="<?php echo CoreDefault::$ViewUrl . 'public/script/captcha.php'; ?>" alt="Captcha Image" style="margin-bottom: 10px;">
							<button id="reloadCaptcha" onclick="reloadCaptcha()" style="background-color: transparent; border: none; cursor: pointer;">
								<i class="fas fa-sync-alt" style="color:#1384AD"></i>
							</button>
						</div>
					</div>
					<div style="display: flex; align-items: center;">
						<input type="text" id="captchaInput" required style="width: 150px;">
						<button id="captchaButton" onclick="checkCaptcha()">Verificar</button>
					</div>
				</div>
			</div>
		</div>
	</form>
	<script>
		// captcha -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --
		function checkCaptcha() {
			var captchaInput = document.getElementById("captchaInput").value;
			var captchaImg = document.getElementById("captcha");
			if (!captchaInput) {
				alert("Captcha em branco!");
			} else {
				var captchaText = captchaImg.getAttribute("alt"); // Obtém o texto exibido na imagem do captcha
				if (captchaInput === captchaText) {
					alert("Captcha correto!");
				} else {
					alert("Captcha incorreto. Tente novamente.");
					reloadCaptcha();
				}
			}
		}

		function reloadCaptcha() {
			var captchaImg = document.getElementById("captcha");
			if (captchaImg) {
				captchaImg.src = "<?php echo CoreDefault::$ViewUrl . 'public/script/captcha.php'; ?>" + "?" + new Date().getTime(); // Adiciona um parâmetro de data para recarregar a imagem
				document.getElementById("captchaInput").value = ""; // Limpa o campo de input
				generateCaptcha();
			}
		}

		function generateCaptcha() {
			var captcha = Array.from({
				length: 6
			}, () => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789" [Math.floor(Math.random() * 62)]).join("");
			var captchaImg = document.getElementById("captcha");
			if (captchaImg) {
				captchaImg.setAttribute("alt", captcha); // Define o valor do captcha como atributo "alt" da imagem
				captchaImg.src = "<?php echo CoreDefault::$ViewUrl . 'public/script/captcha.php'; ?>" + "?" + new Date().getTime(); // Atualiza o src da imagem gerada
			}
			document.getElementById("captchaInput").value = ""; // Limpa o campo de input
		}
		window.onload = function() {
			var captchaButton = document.getElementById("captchaButton");
			if (captchaButton) {
				captchaButton.onclick = checkCaptcha;
			}
			var reloadButton = document.getElementById("reloadCaptcha");
			if (reloadButton) {
				reloadButton.onclick = reloadCaptcha;
			}
		};
	</script>
</body>

</html>