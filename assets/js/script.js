		$(document).ready(function(){

			setTimeout(function(){
				// Hide the address bar!
				window.scrollTo(0, 1);
			}, 0);

			var toggle = 0;

			$(".m_menu").on("click", function(){
				if (toggle == 0) {
					$(".container").addClass("show_mobile_nav");
					$("nav.mobile").css('left', 0);
					$(".m_menu").addClass("m_menu_gray");
					toggle = 1;
				} else {
					$(".container").removeClass("show_mobile_nav");
					$("nav.mobile").css('left', '-100%');
					$(".m_menu").removeClass("m_menu_gray");
					toggle = 0;
				}
			});

			$("#slider").cycle();

			$(".job_alerts_mailing_list").submit(function(e){
				$this = $(this);

				$.post(
							$this.attr('action'),
							$this.serialize(),
							function (data) {
								if(data.success) {
									$this.parent().slideUp(1000, function(){$this.parent().html("You've successfully subscribed to receive job alerts");}).delay(800).fadeIn(800);
								} else {
									$this.find('.error').html(data.message);
								}
							}
					)
				
				e.preventDefault();
			});

			 CKEDITOR.replace( 'description', {
			 	toolbar: 'Basic',
			 });

		});