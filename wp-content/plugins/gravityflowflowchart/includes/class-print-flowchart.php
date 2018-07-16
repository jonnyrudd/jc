<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Gravity Flow Print Entries
 *
 *
 * @package     GravityFlow
 * @subpackage  Classes/Gravity_Flow_Flowchart_Print_Flowchart
 * @copyright   Copyright (c) 2015-2017, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
class Gravity_Flow_Flowchart_Print_Flowchart {
	/**
	 * Output the print page.
	 *
	 */
	public static function render() {

		if ( ! GFAPI::current_user_can_any( 'gravityflowflowchart_view' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page' ) );
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
			<meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
			<meta name="keywords" content=""/>
			<meta name="description" content=""/>
			<meta name="MSSmartTagsPreventParsing" content="true"/>
			<meta name="Robots" content="noindex, nofollow"/>
			<meta http-equiv="Imagetoolbar" content="No"/>
			<title>
				<?php
				$title       = esc_html__( 'Flowchart', 'gravityflowflowchart' );
				$title       = apply_filters( 'gravityflowflowchart_title_print_flowchart', $title );
				echo esc_html( $title );
				?>
			</title>
		<head>
		<link rel='stylesheet' href='<?php echo gravity_flow_flowchart()->get_base_url() ?>/css/joint<?php echo $min; ?>.css' type='text/css'/>
		<link rel='stylesheet' href='<?php echo gravity_flow_flowchart()->get_base_url() ?>/css/flowchart<?php echo $min; ?>.css' type='text/css'/>

		<?php
		$styles = apply_filters( 'gravityflowflowchart_print_styles', false );
		if ( ! empty( $styles ) ) {
			wp_print_styles( $styles );
		}
		wp_register_script( 'gravityflow_joint_lodash', gravity_flow_flowchart()->get_base_url() . "/js/lodash{$min}.js", array(), gravity_flow_flowchart()->get_version(), true );

		wp_register_script( 'gravityflow_joint_backbone', gravity_flow_flowchart()->get_base_url() . "/js/backbone{$min}.js", array('gravityflow_joint_lodash'), gravity_flow_flowchart()->get_version(), true );



		wp_register_script( 'gravityflow_joint_dagre', gravity_flow_flowchart()->get_base_url() . "/js/dagre{$min}.js", array(), gravity_flow_flowchart()->get_version(), true );
		wp_register_script( 'gravityflow_joint_graphlib', gravity_flow_flowchart()->get_base_url() . "/js/graphlib{$min}.js", array(), gravity_flow_flowchart()->get_version(), true );
		wp_register_script( 'gravityflow_joint_js', gravity_flow_flowchart()->get_base_url() . "/js/joint{$min}.js", array( 'jquery', 'gravityflow_joint_backbone', 'gravityflow_joint_lodash', 'gravityflow_joint_dagre', 'gravityflow_joint_graphlib' ), gravity_flow_flowchart()->get_version(), true );

		wp_register_script( 'gravityflow_flowchart_js', gravity_flow_flowchart()->get_base_url() . "/js/flowchart{$min}.js", array( 'gravityflow_joint_js', 'jquery' ), gravity_flow_flowchart()->get_version(), true );

		$graphJSON = rgpost( 'flowchart-json' );
		$graphScale = rgpost( 'graph-scale' );
		$graphScale = sanitize_text_field( $graphScale );
		$graphJSON = json_decode( $graphJSON, true );

		$scripts = array(
			'gravityflow_flowchart_js',
		);

		$strings = array(
			'vars' => array(
				'steps' => null,
				'paper' => array(
					'gridSize' => '10',
					'drawGrid' => null
				),
				'graphJSON' => $graphJSON,
				'graphScale' => $graphScale,
				'context' => 'print',
			),
		);

		?>
			<script>
				var gravityflow_flowchart_js_strings = <?php echo json_encode( $strings ) ?>
			</script>
		</head>
		<body onload="window.print();">
			<div id="flowchart-container"></div>
		<?php wp_print_scripts( $scripts ); ?>
		</body>
		</html>
		<?php
	}

}
