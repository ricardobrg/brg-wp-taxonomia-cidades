jQuery(document).ready(function($) {
	//Ao selecionar o estado busca as cidades
	$("#estado").change(function() {
		var estado = $('#estado').val();
		jQuery.post(
			ajax_object.ajaxurl, {
			action: 'brg_ajax_cities',
			estado: estado,
			}, function(data) {
				//TODO 
				//Processar o retorno de acordo com a necessidade
				console.log(data);
			});
	});
});
