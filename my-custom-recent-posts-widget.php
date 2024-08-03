<?php
/**
 * Plugin Name: My Custom Recent Posts Widget
 * Description: A custom widget to display recent posts with additional settings.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_Custom_Recent_Posts_Widget extends WP_Widget {

    // Конструктор
    public function __construct() {
        $widget_ops = array(
            'classname' => 'my_custom_recent_posts_widget',
            'description' => 'Выводит посты.',
        );
        parent::__construct( 'my_custom_recent_posts_widget', 'My Custom Recent Posts', $widget_ops );
    }

    // Вывод виджета на фронтенде
    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        $posts_per_page = ! empty( $instance['posts_per_page'] ) ? absint( $instance['posts_per_page'] ) : 10;
        $posts_per_month = ! empty( $instance['posts_per_month'] ) ? absint( $instance['posts_per_month'] ) : 5;
        $show_rating = ! empty( $instance['show_rating'] ) ? $instance['show_rating'] : false;
        $rating_position = ! empty( $instance['rating_position'] ) ? $instance['rating_position'] : 'bottom-left';

        // Запрос всех опубликованных статей
        $recent_posts = new WP_Query( array(
            'posts_per_page' => $posts_per_page,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ) );

        if ( $recent_posts->have_posts() ) {
            $posts_by_month = array();

            // Группировка постов по месяцам
            while ( $recent_posts->have_posts() ) {
                $recent_posts->the_post();
                $month_year = get_the_date( 'F Y' );

                if ( ! isset( $posts_by_month[ $month_year ] ) ) {
                    $posts_by_month[ $month_year ] = array();
                }

                $posts_by_month[ $month_year ][] = get_the_ID();
            }

            wp_reset_postdata();

            foreach ( $posts_by_month as $month_year => $post_ids ) {
                echo '<h3>' . esc_html( $month_year ) . '</h3>';
                echo '<ul class="rating-panel-wrap">';

                $total_rating = 0;
                $post_count = count( $post_ids );

                // Рассчитываем средний рейтинг за месяц
                foreach ( $post_ids as $post_id ) {
                    $rating = get_post_meta( $post_id, 'rating', true );
                    if ( $rating ) {
                        $total_rating += floatval( $rating );
                    }
                }

                if ( $show_rating && $post_count > 0 ) {
                    $average_rating = $post_count > 0 ? $total_rating / $post_count : 0;
                    echo '<li>Средний рейтинг за месяц: ' . esc_html( round( $average_rating, 2 ) ) . '</li>';
                }

                // Ограничиваем количество выводимых постов
                $post_ids_to_display = array_slice( $post_ids, 0, $posts_per_month );

                // Запрос для отображения постов
                $posts_to_display = new WP_Query( array(
                    'post__in' => $post_ids_to_display,
                    'orderby' => 'post__in',
                ) );

                if ( $posts_to_display->have_posts() ) {
                    while ( $posts_to_display->have_posts() ) {
                        $posts_to_display->the_post();

                        echo '<li class="custom-recent-post-item">';
                        echo '<div class="post-title">';
                        echo '<a href="' . get_permalink() . '"><span class="desktop-title">' . get_the_title() . '</span><span class="mobile-title">' . wp_trim_words( get_the_title(), 7, '...' ) . '</span></a>';
//                        echo '</div>';
//                        echo '<div class="post-meta">';
                        echo get_the_date() . ' - ';

                        if ( $show_rating ) {
                            $rating = get_post_meta( get_the_ID(), 'rating', true );
                            if ( $rating ) {
                                echo 'Рейтинг: ' . esc_html( $rating );
                            } else {
                                echo 'Рейтинг: не указан';
                            }
                        }
                        echo '</div>';

                        // Добавляем панель голосования
                        echo '<div class="rating-panel ' . esc_attr( $rating_position ) . ' ">';
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo '<a href="#" class="rating-star" data-post-id="' . get_the_ID() . '" data-rating="' . $i . '">' . $i . '</a> ';
                        }
                        echo '</div>';

                        echo '</li>';
                    }
                }

                wp_reset_postdata();

                echo '</ul>';
            }
        } else {
            echo 'No recent posts found.';
        }

        echo $args['after_widget'];
    }

    // Форма виджета в админке
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $show_rating = ! empty( $instance['show_rating'] ) ? (bool) $instance['show_rating'] : false;
        $rating_position = ! empty( $instance['rating_position'] ) ? $instance['rating_position'] : 'bottom-left';
        $posts_per_month = ! empty( $instance['posts_per_month'] ) ? absint( $instance['posts_per_month'] ) : 3;
        $posts_per_page = ! empty( $instance['posts_per_page'] ) ? absint( $instance['posts_per_page'] ) : 3;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_rating ); ?> id="<?php echo $this->get_field_id( 'show_rating' ); ?>" name="<?php echo $this->get_field_name( 'show_rating' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_rating' ); ?>">Show Rating</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'posts_per_page' ); ?>">Количество постов всего:</label>
            <input id="<?php echo $this->get_field_id( 'posts_per_page' ); ?>" name="<?php echo $this->get_field_name( 'posts_per_page' ); ?>" type="number" value="<?php echo esc_attr( $posts_per_page ); ?>" size="3" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'posts_per_month' ); ?>">Количество постов в месяце:</label>
            <input id="<?php echo $this->get_field_id( 'posts_per_month' ); ?>" name="<?php echo $this->get_field_name( 'posts_per_month' ); ?>" type="number" value="<?php echo esc_attr( $posts_per_month ); ?>" size="3" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'rating_position' ); ?>">Rating Position:</label>
            <select id="<?php echo $this->get_field_id( 'rating_position' ); ?>" name="<?php echo $this->get_field_name( 'rating_position' ); ?>" class="widefat">
                <option value="bottom-left" <?php selected( $rating_position, 'bottom-left' ); ?>>Bottom Left</option>
                <option value="bottom-center" <?php selected( $rating_position, 'bottom-center' ); ?>>Bottom Center</option>
                <option value="bottom-right" <?php selected( $rating_position, 'bottom-right' ); ?>>Bottom Right</option>
            </select>
        </p>
        <?php
    }

    // Сохранение настроек виджета
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['show_rating'] = isset( $new_instance['show_rating'] ) ? (bool) $new_instance['show_rating'] : false;
        $instance['rating_position'] = ( ! empty( $new_instance['rating_position'] ) ) ? sanitize_text_field( $new_instance['rating_position'] ) : 'bottom-left';
        $instance['posts_per_month'] = ( ! empty( $new_instance['posts_per_month'] ) ) ? absint( $new_instance['posts_per_month'] ) : 5;
        $instance['posts_per_page'] = ( ! empty( $new_instance['posts_per_page'] ) ) ? absint( $new_instance['posts_per_page'] ) : 10;
        return $instance;
    }
}

// Регистрация виджета
function register_my_custom_recent_posts_widget() {
    register_widget( 'My_Custom_Recent_Posts_Widget' );
}
add_action( 'widgets_init', 'register_my_custom_recent_posts_widget' );

// Подключение стилей и скриптов
function my_custom_recent_posts_widget_styles_scripts() {
    wp_enqueue_style( 'custom-recent-posts-widget', plugin_dir_url( __FILE__ ) . 'style-custom.css' );
    wp_enqueue_script( 'custom-recent-posts-widget', plugin_dir_url( __FILE__ ) . 'script-custom.js', array('jquery'), null, true );

    // Локализация скрипта для передачи ajaxurl
    wp_localize_script( 'custom-recent-posts-widget', 'ajax_vars', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    ) );
}
add_action( 'wp_enqueue_scripts', 'my_custom_recent_posts_widget_styles_scripts' );

// AJAX обработчик для сохранения рейтинга
function handle_rating_ajax() {
    if ( isset( $_POST['post_id'] ) && isset( $_POST['rating'] ) ) {
        $post_id = absint( $_POST['post_id'] );
        $rating = floatval( $_POST['rating'] );

        // Сохранение рейтинга
        update_post_meta( $post_id, 'rating', $rating );

        wp_send_json_success( 'Rating updated successfully.' );
    } else {
        wp_send_json_error( 'Invalid request.' );
    }
}
add_action( 'wp_ajax_handle_rating', 'handle_rating_ajax' );
add_action( 'wp_ajax_nopriv_handle_rating', 'handle_rating_ajax' );
?>
