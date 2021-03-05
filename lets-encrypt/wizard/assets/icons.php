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
        'documents-sync' => array(
            'success' => array(
                'type' => 'svg',
                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" height="' . $size . '" viewBox="0 0 ' . $vb . ' ' . $vb . '" width="' . $size . '"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>',
                'tooltip' =>__( 'Document is kept up to date by Complianz', 'complianz-terms-conditions' ),
            ),
            'disabled' => array(
                'type' => 'svg',
                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" height="' . $size . '" viewBox="0 0 ' . $vb . ' ' . $vb . '" width="' . $size . '"><path d="M10 6.35V4.26c-.8.21-1.55.54-2.23.96l1.46 1.46c.25-.12.5-.24.77-.33zm-7.14-.94l2.36 2.36C4.45 8.99 4 10.44 4 12c0 2.21.91 4.2 2.36 5.64L4 20h6v-6l-2.24 2.24C6.68 15.15 6 13.66 6 12c0-1 .25-1.94.68-2.77l8.08 8.08c-.25.13-.5.25-.77.34v2.09c.8-.21 1.55-.54 2.23-.96l2.36 2.36 1.27-1.27L4.14 4.14 2.86 5.41zM20 4h-6v6l2.24-2.24C17.32 8.85 18 10.34 18 12c0 1-.25 1.94-.68 2.77l1.46 1.46C19.55 15.01 20 13.56 20 12c0-2.21-.91-4.2-2.36-5.64L20 4z"/></svg>',
                'tooltip' => __( 'Document is not kept up to date by Complianz', 'complianz-terms-conditions' )
            )
        ),
        'documents-shortcode' => array(
            'success' => array(
                'type' => 'dashicons',
                'icon'    => 'dashicons-shortcode',
                'tooltip' =>__( 'Click to view the document shortcode', 'complianz-terms-conditions' ),
            ),
            'success_notooltip' => array(
                'type' => 'dashicons',
                'icon'    => 'dashicons-shortcode',
            ),
            'disabled' => array(
                'type' => 'dashicons',
                'icon'    => 'dashicons-shortcode',
            )
        ),
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
        'calendar' => array(
            'success' => array(
                'type' => 'svg',
                'icon' => '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="calendar-check" class="svg-inline--fa fa-calendar-check fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M436 160H12c-6.627 0-12-5.373-12-12v-36c0-26.51 21.49-48 48-48h48V12c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v52h128V12c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v52h48c26.51 0 48 21.49 48 48v36c0 6.627-5.373 12-12 12zM12 192h424c6.627 0 12 5.373 12 12v260c0 26.51-21.49 48-48 48H48c-26.51 0-48-21.49-48-48V204c0-6.627 5.373-12 12-12zm333.296 95.947l-28.169-28.398c-4.667-4.705-12.265-4.736-16.97-.068L194.12 364.665l-45.98-46.352c-4.667-4.705-12.266-4.736-16.971-.068l-28.397 28.17c-4.705 4.667-4.736 12.265-.068 16.97l82.601 83.269c4.667 4.705 12.265 4.736 16.97.068l142.953-141.805c4.705-4.667 4.736-12.265.068-16.97z"></path></svg>',
            ),
            'error' => array(
                'type' => 'svg',
                'icon' => '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="calendar-times" class="svg-inline--fa fa-calendar-times fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M436 160H12c-6.6 0-12-5.4-12-12v-36c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h48c26.5 0 48 21.5 48 48v36c0 6.6-5.4 12-12 12zM12 192h424c6.6 0 12 5.4 12 12v260c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V204c0-6.6 5.4-12 12-12zm257.3 160l48.1-48.1c4.7-4.7 4.7-12.3 0-17l-28.3-28.3c-4.7-4.7-12.3-4.7-17 0L224 306.7l-48.1-48.1c-4.7-4.7-12.3-4.7-17 0l-28.3 28.3c-4.7 4.7-4.7 12.3 0 17l48.1 48.1-48.1 48.1c-4.7 4.7-4.7 12.3 0 17l28.3 28.3c4.7 4.7 12.3 4.7 17 0l48.1-48.1 48.1 48.1c4.7 4.7 12.3 4.7 17 0l28.3-28.3c4.7-4.7 4.7-12.3 0-17L269.3 352z"></path></svg>',
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