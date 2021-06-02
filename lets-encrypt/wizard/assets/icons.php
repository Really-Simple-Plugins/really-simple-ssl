<?php

/**
 * Get icon from predefined list
 * https://material.io/resources/icons/?search=sync&icon=sync_disabled&style=outline
 * @param string $icon_name
 * @param string $status
 * @param string $tooltip (optional)
 *
 * @return string
 */
function rsssl_icon( $icon_name, $status, $tooltip = '') {
    $size = 14;
    $vb = 22;
    $icons = array(
        'bullet' => array(
            'success' => array(
                'type' => 'css',
                'icon' => 'bullet',
            ),
            'disabled' => array(
                'type' => 'css',
                'icon' => 'bullet',
            )
        ),
        'check' => array(
            'success' => array(
                'type' => 'svg',
                'icon' => '<svg width="10" height="10" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"/></svg>',
            ),
            'green' => array(
                'type' => 'svg',
                'icon' => '<svg width="10" height="10" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"/></svg>',
            ),
            'prefilled' => array(
                'type' => 'svg',
                'icon' => '<svg width="10" height="10" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"/></svg>',
            ),
            'error' => array(
                'type' => 'svg',
                'icon' => '<svg width="10" height="10" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1490 1322q0 40-28 68l-136 136q-28 28-68 28t-68-28l-294-294-294 294q-28 28-68 28t-68-28l-136-136q-28-28-28-68t28-68l294-294-294-294q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 294 294-294q28-28 68-28t68 28l136 136q28 28 28 68t-28 68l-294 294 294 294q28 28 28 68z"/></svg>',
            ),
            'failed' => array(
                'type' => 'svg',
                'icon' => '<svg width="10" height="10" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1490 1322q0 40-28 68l-136 136q-28 28-68 28t-68-28l-294-294-294 294q-28 28-68 28t-68-28l-136-136q-28-28-28-68t28-68l294-294-294-294q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 294 294-294q28-28 68-28t68 28l136 136q28 28 28 68t-28 68l-294 294 294 294q28 28 28 68z"/></svg>',
            ),
            'empty' => array(
                'type' => 'svg',
                'icon' => '<svg width="10" height="10" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"/></svg>',
            ),
        ),
        'arrow-right-alt2' => array(
            'success' => array(
                'type' => 'dashicons',
                'icon'    => 'dashicons-arrow-right-alt2',
            ),
        ),
        'arrow-right' => array(
            'normal' => array(
                'type' => 'dashicons',
                'icon'    => 'dashicons-arrow-right',
            ),
        ),
        'sync' => array(
            'success' => array(
                'type' => 'dashicons',
                'icon' => 'dashicons-update',
            ),
            'error' => array(
                'type' => 'dashicons',
                'icon' => 'dashicons-update',
            ),
            'disabled' => array(
                'type' => 'dashicons',
                'icon' => 'dashicons-update',
            ),
        ),
        'file' => array(
            'success' => array(
                'type' => 'dashicons',
                'icon' => 'dashicons-media-default',
            ),
            'disabled' => array(
                'type' => 'dashicons',
                'icon' => 'dashicons-media-default',
            ),
        ),
        'help' => array(
            'normal' => array(
                'type' => 'dashicons',
                'icon' => 'dashicons-editor-help',
            ),
        )
    );

    if ( $tooltip ) {
        $tooltip =  'rsssl-tooltip="' . $tooltip . '" flow="right"';
    } else if ( isset($icons[$icon_name][$status]['tooltip']) ) {
        $tooltip =  'rsssl-tooltip="' . $icons[$icon_name][$status]['tooltip'] . '" flow="left"';
    }

    $icon = $icons[$icon_name][$status]['icon'];
    $type = $icons[$icon_name][$status]['type'];

    if ( $type === 'svg' ){
        $html = '<div class="rsssl-tooltip-icon dashicons-before rsssl-icon rsssl-' . esc_attr( $status ) . ' ' . esc_attr($icon_name) . '" >' . $icon . '</div>';
    } else if ( $type === 'dashicons' ) {
        $html = '<div class="rsssl-tooltip-icon dashicons-before rsssl-icon rsssl-' . esc_attr( $status ) . ' ' . esc_attr($icon_name) . ' ' . $icon . '" ></div>';
    } else {
        $html = '<div class="rsssl-icon rsssl-bullet rsssl-' . esc_attr( $status ) . ' ' . esc_attr($icon_name) . ' ' . $icon . '" ></div>';
    }

    return '<span '.$tooltip.'>'.$html.'</span>';
}