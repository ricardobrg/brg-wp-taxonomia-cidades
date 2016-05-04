<?php
/*
Plugin Name: BRG - Cidades e Estados do Brasil
Description: EM DESENVOLVIMENTO - Plugin em desenvolvimento para facilitar a importação de cidades e estados. 
Cria também a base para um menu suspenso de cidades que será povoado por AJAX de acordo com a seleção do estado. 
Baseado no código de @brunomarks (https://gist.github.com/brunomarks/8851573).
Version: 0.1
Author: BRGWeb
Author URI: http://brgweb.com.br
License: GPLv2 ou superior
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/* Adiciona opção no menu para criar os termos de estado e cidade. 
 * Pela quantidade de registros, essa função pode causar timeout
 * ou estouro de memória em alguns servidores. 
 * Para evitar isso, verificamos se o estado/cidade já está cadastrado.
 * Assim é possível rodar a função várias vezes até todos
 * os termos estarem cadastrados.
 * Após a criação comente a primeira linha após esse bloco de comentário
 * para retirara a opção do tema.
 * TODO 
 * Processar essa importação em lotes por AJAX para evitar timeout ou
 * estouro de memória.
 * Remover automaticamente a opção do menu.
*/
add_action ('admin_menu', 'brg_add_menu_tmp');
function brg_add_menu_tmp(){
add_menu_page ( "importar termos", "importar termos", "manage_options", "importar_termos", "brg_create_location_terms", '', 0.1 );
}
function brg_create_location_terms() {
	// busca o feed com todos os estados e respectivas cidades
	$feed = json_decode(file_get_contents(plugin_dir_path( __FILE__ ) . 'brazil-cities-states.json')); 
	foreach ($feed->estados as $key => $estado) {
		$sigla = $estado->sigla;
		// busca para ver se o estado existe e adiciona
		$estado_term = get_term_by( 'name', $sigla, 'estado' );
		$current_term_id = '';
		if (!$estado_term){
			$estado_term = wp_insert_term($estado->sigla, 'estado');
			$current_term_id = (int)$estado_term['term_id'];
			echo "Novo Estado: " . $estado->sigla ." - ID ".$current_term_id;
		}else{
			$current_term_id = (int)$estado_term->term_id;
			echo "Estado econtrado: " . $estado->sigla ." - ID ".$current_term_id;
		}	
		echo "Adcionando Cidades";	
		foreach ($estado->cidades as $key => $cidade) {
			$cidade_id = get_term_by( 'name', $cidade, 'cidade' );
			if (!$cidade_id){
			$cidade_id = wp_insert_term( $cidade, 'cidade', array( 'parent'=> $current_term_id ) );
			echo "Nova Cidade: " . $cidade ." - ID ".$cidade_id;
			}else{
			echo "Cidade econtrada: " . $cidade ." - ID ".$cidade_id;
			}
		}
	}
}


// Cria taxonomia Estado
add_action('init', 'brg_register_state');
function brg_register_state() {
	register_taxonomy( 
	'estado',
	array( 'post' ), //adicionar aqui os post_types que utilizarão a taxonomia
	array( 'hierarchical' => true,
		'label' => 'Estado',
		'show_ui' => true,
		'query_var' => true,
		'show_admin_column' => true,
		'labels' => array (
			'search_items' => 'Estado',
			'popular_items' => 'Principais Estados',
			'all_items' => 'Todos os Estados',
			'edit_item' => 'Editar item',
			'update_item' => 'Atualizar Estado',
			'add_new_item' => 'Adicionar Estado'
			)
		) 
	); 
}

// cria taxonomia de cidade
add_action('init', 'brg_register_locations');
function brg_register_locations() {
	register_taxonomy( 
	'cidade',
	array( 'post' ), //adicionar aqui os post_types que utilizarão a taxonomia
	array( 'hierarchical' => true,
		'label' => 'Cidades',
		'show_ui' => true,
		'query_var' => true,
		'show_admin_column' => true,
		'labels' => array (
			'search_items' => 'Cidade',
			'popular_items' => 'Cidades populares',
			'all_items' => 'Todos as cidades',
			'edit_item' => 'Editar item',
			'update_item' => 'Atualizar cidade',
			'add_new_item' => 'Adicionar cidade'
			)
		) 
	); 
}

// função auxiliar para buscar as cidades do estado
function brg_get_cities($estado = ''){
	$cidades = array();
	if (!empty($estado)){
		$cidades = get_term_children( $estado, 'cidade');
	}else{
		$cidades = get_terms('cidade');
	}
	return $cidades;
}

// função auxiliar para buscar os estados
function brg_get_estados(){
	$estados = get_terms('estado');
	return $estados;
}

// função para processar a busca pelas cidades por chamada ajax
add_action( 'wp_ajax_brg_ajax_cities', 'brg_ajax_cities' );
add_action( 'wp_ajax_nopriv_brg_ajax_cities', 'brg_ajax_cities' );
function brg_ajax_cities(){
	$estado = $_POST['estado'];
	$cidades = brg_get_cities($estado);
	// Apenas fazendo dump do retorno para chechar no console.
	var_dump($cidades);
	//TODO 
	// Alterar o retorno de acordo com a necessidade
	die();
}

// enfileirar o javascript que vai processar a busca por ajax
add_action( 'wp_enqueue_scripts', 'brg_ec_scripts');
function brg_ec_scripts() {
    
    wp_enqueue_script(
        'brgscripts', 
        plugin_dir_url( __FILE__ ) . 'js/brgscripts.js', 
        array('jquery'), 
        '',
        true
    );
	wp_localize_script( 
		'brgscripts', 
		'ajax_object', 
		array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) 
	); 
}

