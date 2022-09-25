<?php
namespace TechSarathy\Settings\Base;
abstract class SettingsBase {
    protected $id_base;
    protected $page_title='';//settings page tite
    protected $menu_title='';//menu title
    protected $capability = 'manage_options';
    protected $slug = '';//page_slug
    protected $setting_field= '';//setting field//option group name
    protected $setting_section = '';//setting section
    protected $options = [];//store all options
    public static $allFields;
    protected static $_instance = null;
  
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
              self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct() {
        if ( ! empty( $this->id_base ) ) {
			$id_base = strtolower( $this->id_base );
		} else {
			$id_base =  strtolower( get_class( $this ) ) ;
		}
        $this->id_base         = $id_base;
        if ( empty( $this->slug ) ) {
			$slug = 'setting_'.$id_base;
		}
        if(empty($this->setting_field)){
            $this->setting_field = 'setting_option_'.$id_base;
        }
        $this->slug            = $slug;
        $this->page_title      = __( $this->page_title, 'ts-settings' );
        $this->menu_title      = __( $this->menu_title, 'ts-settings' );
        add_action( 'admin_menu', array( $this, 'ts_create_settings' ) );
        add_action( 'admin_init', array( $this, 'ts_setup_sections' ) );
        add_action( 'admin_init', array( $this, 'ts_setup_fields' ) );
        add_action( 'admin_head', array( $this, 'my_custom_admin_head' ));
        add_action( 'admin_enqueue_scripts', array($this,'ts_include_js') );
        
    }

    private function initialize_options(){
        foreach(self::$allFields as $key=>$field){
            if($field['type']=='repeater'){
                $main_value=json_decode(get_option( $field['id'],(isset($field['std'])?$field['std']:'') ));
                $main_value=(array_map(array(__CLASS__,'formt'),$main_value));
                $main_value=array_map(array(__CLASS__,'flt'),$main_value);
                $this->options[$field['id']]=$main_value;
            }else{
                $this->options[$field['id']]=get_option($field['id'],(isset($field['std'])?$field['std']:''));
                self::$allFields[$key]['value']=$this->options[$field['id']];
            }
            
        }
    }
    public function get_all_options(){
        return $this->options;
    }
    public static function get_option_field($key){
        if(isset($this->options[$key]) && !empty($this->options[$key])){
            return $this->options[$key];
        }
        return get_option($key);
    }
    public static function get_fields(){
        return apply_filters('ts_settings_get_fields',self::$allFields,$this->id_base);
    }

    
    public function ts_include_js(){
        if ( ! did_action( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }
    }
    public function my_custom_admin_head()
    {
    ?>
    <style>
        .ts-settings td label{margin:0px 10px 10px 10px;display:inline-block;}.ts-settings .ts-repeater-remove{background:url('data:image/png;base64,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') no-repeat;width:20px;
            height:20px;
            display:inline-block;
            background-size:contain;
        }
        .ts-settings .ts-repeater-addmore{	background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAlzAAAJcwBSAPCGQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAyDSURBVHic7Z17cFTXfcc/v7urFcgPCcsE0toEQojHMklLadKJAVtPDHiQBHRxLAFWnQxu6+kjkzbpxJ5WDq2T1naaiaed2Ek72AKMRawHcs1LSJsa/MKPejDOJAZDCg3GRoBMENZq9/76B5JAoNdq796zks7nL/bqnt/vK85Xe+8995zzE8YaihTXVcxQkTwRZigyHWGaCpNRcgVyBSYohICruludE4gqfKLQhtDmKB+qclTQIzjyPjH3F83LNx1GUHO/nPeIaQHJUti05nedmDtPRecBXwKZDVyTonRngf0o+9SRvQG69u4q2/ybFOXyhVFngPzWqgmBj2P5qC4GWQR83rCkX4rINpTtGaGcyLYlj3ca1pMQo8IA4dpw6FQwtFAcVipSBlxrWtMAtAONItSe/iBr5xv3PdllWtBQpLUBSpoqZmnc+ZpCFTDFtJ4EOQH6rOsEn2wtfeqAaTEDkZYGKK5bM18D+m1U7yRNNSaCwl6Qf24pq3k+3W4i0+c/V5HCxspyQf4e+H3TclKC8qYE3O82L920NV2MkBYGKKyvXOyIs07Ruaa1+MTrgjzYXF6zw7QQowYoaFx1k6M8BtxpUoc5tDngOt/YubzmHVMKjBhgadParI5YxzqEvwSCJjSkEV2I/mtHZ7T65ZVbzvud3HcDFDesKVZxn0D5rN+50xmBg64j97WU1rT4nNcf8lurJgTbY9UKfws4fuUdZSjCT7KcrG80LX2yw4+EvhigqHHNF9H4JpBb/Mg3BtjvOoG7/Rg/SPlfYmH9qrtR9yXb+QnxBceN7ytqrLwn1YlS9g0Qrg0HTmdOeFRV/zpVOcYFIo8teGvmt6qrq92UhE9F0HBtONSWmVkjyspUxB+H1MezgxWRgvWfeB3YcwPk14avDoYyn1NY6HXs8Y20hkJu+bYlGz/2NKqXwYoa756CG3gB4Q+8jGu5gCBvOC5Ldi6v+dC7mB6RX181PSixXQqf8yqmpR+U9+IEF0aWrT/iRThPDLC49k8mR0NdLwI3eRHPMiSH4hnB+ZE713+QbKCkHwMXv1B5bTSjazu28/1kZqArtiO/vion2UBJGSBcGw5FO+Vn9ppvhC8GJF63+IW/yEwmyIgNUF1d7ZwOZW5AKElGgCUZtCAaPb05XBsOjDTCiA2wZ86hxxTCI21v8YzyU5mhh0faeEQ3gcUNlRWKbBxpUovnqDjusubSTY2JNkzYABde7LgvA1mJtrWklLYAsTk7yzcfTaRRQpeA/NaqCai7Adv56UiuS3DL3CfWZiTSKCEDBM/EHgG+kJAsi28o/NGkKR3rEmkz7EtAccOaYsXdmUgbixHUcaVg1/Kanw/n5GF9AyxtWpuluD/Gdv5oQFxH/324l4JhGaAj1rEOmJmUrDRDEGZlT2dW9nRk7Pk6b9LUjmHNwxjyNy+uW32zOvo2kNDNRToTcjL4p6/8DXOuzwPgrZPv8sDLjxJ1034pXyJ0aNzNa1mx6deDnTTkN4A6+gPGUOcDLJy2oLfzAeZcn8fCaQsMKkoJWRJwHh3qpEENUFhfuRhY5JmkNOGGq6cO69gY4I9L6iuLBjthYAMoIshDnktKA/q75o/B+wAAXJHvDvbzAQ1Q2FhZjvAl7yVZfObWooZVA17fBjRA9ypdyxhA4MGBftavAYob1hQzVpdoj0MUFhZtrez327xfAyjuN1MryeI7rnynv8NXGKCkqWIWcEfKBVn8pqyw/p4rBvOuMIDrytexQ75jERGJ33v5wT4GmPvE2gxU1vinyeIz917+jqCPAbKnnF8EjMkREQsAU3OmdvQZGOpjAFG1a/nGOqp3Xfqx1wD5rVUTEEr9V2TxFykP14ZDPZ96DRD4OJZP+u7AafGOnNOhibf1fLh4CVBdbESOxXdUtPcF3yUGEGuA8cIlf+wOXNhyHWGWOUUWn8m747lVn4ZuA0g8Pt+sHovfxB1uhYuXgFsNarEYwBXmwUUD2Pf+448vAzjV1dUOiF3sMc4QmI0iTmTOwc8CV5sWZPGd7MK6immOo3KzaSUWQziS5wjMMK3DYgYHme6g+hnTQixmUGGGgzDNtBCLGVT4jAN8yrQQixlEZbIDmmtaiMUUmuuAXGdahsUYuUEMbfcyeeJ1FN8wj6sy/E8/O/fKarOzcz/P1/Pu6ufs1HKuq4PmY3v56Pwp33MLZElRw6oOYKKfiSdPvI4f3/6PZGemqsbz6KK98yx/+vMHTZigw+FCGXVfKblxvu38S8jOvIaSG428kM20xZvGOQ4Q9TvprqN7aO8863fatKW98yy7ju4xkbpTihpWnQGy/c5s8iZwzuQ8bsrpW7bwl2fe562P3vVdi+GbwNNB4BwGDPDR+VM8816T32kB+LPZlVcY4J22X/HTd581oscUCuccBf+tZ0kPhDZH4KRpHRYzqHLSwRpg/CK0OSj/a1qHxQyCHnEEPWJaiMUM4soRR5XDpoVYzKAOhx1R9f/h15IWBDR2wGlevukw4Gk5UsuooH1n2eZjDoIC75hWY/EZYT+CXngZpOwzLMfiO/IqdC8NU0f2mhVj8RtVdy90GyBAlzXAOMPNyHgZug2wq2zzb4BfGVVk8RE90FN4+pIdQthmTI/FVwTp7eteA4iINcA4QVS39/y71wAZoZwI0G5CkMVXzuR0RV/s+dBrgG1LHu8EEq49axllqNRtWbmldxpg351ChVr/FVn8RHH7THvqY4DYtcd2AMd9VWTxkw/aP7yq9dIDfQwQKYjEBJ72V5PFLwT5jzfue7JPccQr1gXEhJ8C6psqi18o8fh/Xn7wCgNEyjYcFOyYwBjk+eYVm96//GC/K4NcRx5LvR6LzzzS38F+DdBSWtOC8mZq9Vj8QuDV3eUbXuzvZwPXDQy4g1actIwe4gxcPXRAAzSXbmoEeS01kiy+oexrLasZ8J5u0NXBArZ66ChHA/J33bO++mVQAzSX1+wQeMF7WRZ/kGdbSmtaBjtjyP0BYsJfYWAJuSVpzmpAhqwAO6QBImUbDiL6Q280pQdRt2tYx0Y1QnXL0qf/b6jThrVDSEdntFrgYPKq0oOXjr+J6sXLoqry0vGx9NSrB858kPX4cM4cdonYwq2rC8XV5kTapDPzP/2HLJ95oURy3aEd7Dn+umFFnuEC+QM9919OQp1ZVL/qh1y4J7CkLw/vLt/wwHBPTmiTqFDmpG8jvJ24JotPvHLmRFZ1Ig0S/jov2HrPLY4bfxW4KtG2lpRyMh7XOZEVG48l0ijhbeJaS586oOhq7CvjdEJd4WuJdj6MwAAALeUb6xH5wUjaWlKA6vdbyzZsHUnTEW8UueCtmd8CGV/baqUnmxe8PevBkTZO6pFu7hNrM3KmnGsCuSOZOJYRorSEMict6Z7RPSKSfqaf13jvNRO1q1XRucnGsiTE6/FoZ0Fk5ZbfJhPEk0Gd/Ka7rw/EA3uAm7yIZxmSQ0h83u6yZ04kG8iTzaIjS585GdfgIpT3vIhnGQTlPY27RV50PnhkAIDIsvVHUOcrwCtexbRcwesBlfktKzb92quAno/r59eGrw6EQj+zN4Yeo7SEMnXZtiUbPd3PyfN6AZGVW357XTRaCmz2OvZ4RVXr4jnBO73ufEiBAQC2rNwSXfA/n6tE9F+wI4bJoKh+77a3Z4UjBes/SUWClL/aLW5YU6y4G4Apqc41xjjpIve0ltekdEqeL+/285+rvCEQlGdQjBTGGX3Ia3EN3BVZtv5IqjP5UjMosmLjsfi1xwpEeYgLExYs/aOgPzpzYuJ8PzofDMzuKalbfbvruP8GcovfudOc/cD9w53J4xVGpnflt+YHAx/fcD/KOmC814/rEOWRSV2dD1+6c4dfGJ3fV9L41d9RDXxfkdUmdRhD5Hlx5P7mpU8bq9mQFhM8S+ori1yRfwAWmNbiE6+oIw8MtWjDD9LCAD0UNlbcJirfGbujiPKaCw+l+tEuEdLKAD0U1K36vYCj31SkAgiY1pMkCrpbcX7UUl5jpk7eIKSlAXpY2PDVG2MEKwT+HJhmWk+CHBd42tXAT1qWPXXItJiBSGsD9ND91FCM6l0g5UCOaU39IXAaaAB5NpZ9dHekIBIzrWkoRoUBLiVcGw6dysy8HZdFCIuAPLOK9ADibBeV7ZOi5//bxKNcMow6A1xO/n9VTQ1GY/NcYR7wZYHZpK4UbjvCflX2OSJ7lNheryZmmGLUG6A/8uurpjt03SzCDJDpINOAT4HmArkCWQpBLg5CnRWIKXQAbSBtoCeAo6IcVofDGnN/4eVEjHTh/wF3jMnanITh4AAAAABJRU5ErkJggg==') no-repeat;
            width:20px;
            height:20px;
            display:inline-block;
            background-size:contain;
        }
        .ts-settings .ts-settings-upl img{width:50px;}
    </style>
    <?php
    }
    public function ts_create_settings() {
          $callback = array($this, 'ts_settings_content');
          add_options_page($this->page_title, $this->menu_title, $this->capability, $this->slug, $callback);
    }
    public function ts_settings_content() {
        $this->initialize_options();
    ?>
  
          <div class="ts-settings wrap">
              <h1><?php echo $this->page_title?></h1>
              <?php settings_errors(); ?>
              <form method="POST" action="options.php">
                  <?php
                      settings_fields( $this->setting_field );
                      do_settings_sections( $this->setting_field );
                      submit_button();
                  ?>
              </form>
          </div>
  <script>
  jQuery(function($){
  
      // on upload button click
      $('body').on( 'click', '.ts-settings-upl', function(e){
  
          e.preventDefault();
  
          var button = $(this),
          custom_uploader = wp.media({
              title: 'Insert image',
              library : {
                  // uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                  type : 'image'
              },
              button: {
                  text: 'Use this image' // button label text
              },
              multiple: false
          }).on('select', function() { // it also has "open" and "close" events
              var attachment = custom_uploader.state().get('selection').first().toJSON();
              button.html('<img src="' + attachment.url + '">').next().show().next().val(attachment.id);
          }).open();
  
      });
  
      // on remove button click
      $('body').on('click', '.ts-settings-rmv', function(e){
  
          e.preventDefault();
  
          var button = $(this);
          button.next().val(''); // emptying the hidden field
          button.hide().prev().html('Upload image');
      });
  
  });
  </script>
  <?php
    }
    public function ts_setup_sections() {
        add_settings_section( $this->setting_section, '', array(), $this->slug );
    }

    public function ts_setup_fields() {
        $fields = self::get_fields();//=$fields;
        foreach( $fields as $field ){
            add_settings_field( $field['id'], $field['label'], array( $this, 'ts_field_callback' ), $this->slug, $field['section'], $field );
            register_setting( $this->setting_field, $field['id'] );
        }
        $this->initialize_options();
    }

    public function radio_field($field){
      
        foreach($field['option'] as $key=>$label){
            $this->radio_checkbox($field['id'],$field['type'],$field['placeholder'],$key,$field['value'],$label);
        }
  
    }
    public function radio_checkbox($id,$type,$placeholder,$val,$curVal,$label){
        $name=$id;
        if($type=='checkbox'){
            $name=$id.'[]';

        }
        $id=$id.'_'.$val;
        printf('<label for="%1$s"><input name="%2$s" class="%1$s" type="%3$s" placeholder="%4$s" value="%5$s" "%6$s" />%7$s</label>',$id,$name,
                        $type,
                        $placeholder,
                        $val,checked($val,$curVal,false),$label);
    }
    public function checkbox_field($field){
        if(count(array_filter(array_keys($field['option']), 'is_string')) > 0){
            foreach($field['option'] as $key=>$label){
                $this->radio_checkbox($field['id'],$field['type'],$field['placeholder'],$key,$field['value'],$label);
            }
        }else{
            foreach($field['option'] as $key){
                $label=ucwords(str_replace('_',' ',$key));
                $this->radio_checkbox($field['id'],$field['type'],$field['placeholder'],$key,$field['value'],$label);
            }
        }
    }
    private function select_field($field){
        $value = !isset($field['value'])?get_option( $field['id'] ):$field['value'];
        printf('<select name="%1$s" class="%2$s">',isset($field['name'])?$field['name']:$field['id'],$field['id']);
        foreach($field['option'] as $option){
            printf('<option value="%1$s" "%2$s">%1$s</option>',$option,selected($option,$value,false));
        }
        printf('</select>');
    }
    public function ts_field_callback( $field ) {

        switch ( $field['type'] ) {
        case 'radio':$this->radio_field($field);
        break;
            case 'repeater':$this->repeater_field($field);break;
            case 'select':$this->select_field($field);break;
            case 'media':$this->media_field($field);break;
            default:
                $value = !isset($field['value'])?get_option( $field['id'] ):$field['value'];
                printf( '<input name="%2$s" class="%1$s" type="%3$s" placeholder="%4$s" value="%5$s" />',
                    $field['id'],(isset($field['name'])?$field['name']:$field['id']),
                    $field['type'],
                    $field['placeholder'],
                    $value
                );
        }
        if( $desc = $field['desc'] ) {
            printf( '<p class="description">%s </p>', $desc );
        }
    }
    public static function formt($val){
        if(is_array($val))
            return array_map(array(__CLASS__,'formt'),$val);
        return explode(':',$val);
    }
    public static function flt($v){
        $t=[];
        foreach($v as $k){
            $t[$k[0]]=$k[1];
        }
        return $t;
    }
    private function repeater_field($field){
        $parent_label=$field['label'];
        $parent_id=$field['id'];
        $parent_section=$field['section'];
        $sub_fields=$field['sub_fields'];
        $main_value=json_decode(get_option( $field['id'] ));
        $main_value=(array_map(array(__CLASS__,'formt'),$main_value));
        $main_value=array_map(array(__CLASS__,'flt'),$main_value);

        ob_start();
        echo '<div class="ts-repeater-field">';
            foreach($sub_fields as $sub_field){
                $value = get_option( $sub_field['id'] );
                //$method=$sub_field['type'].'_field';
                $sub_field['name']=$sub_field['id'].'[]';
                $this->ts_field_callback($sub_field);
            }
        echo '<a class="ts-repeater-addmore" href=""></a></div>';
        if(is_array($main_value) && count($main_value)>0){
            foreach($main_value as $main){
                echo '<div class="ts-repeater-field">';
                foreach($sub_fields as $sub_field){
                    $value = $main[$sub_field['id']];
                    //echo $value;
                    $sub_field['value']=$value;
                    //$method=$sub_field['type'].'_field';
                    $sub_field['name']=$sub_field['id'].'[]';
                    $this->ts_field_callback($sub_field);
                }
                echo '<a class="ts-repeater-remove" href=""></a></div>';
            }

        }

        $out=ob_get_clean();
        printf( '<div class="ts-repeater-container">%1$s<textarea style="display:none;" id="%2$s" name="%2$s"></textarea></div>', $out,$parent_id );
        ?>
        <script language="javascript">
            (function (original) {
        jQuery.fn.clone = function () {
            var result           = original.apply(this, arguments),
                my_textareas     = this.find('textarea').add(this.filter('textarea')),
                result_textareas = result.find('textarea').add(result.filter('textarea')),
                my_selects       = this.find('select').add(this.filter('select')),
                result_selects   = result.find('select').add(result.filter('select'));

            for (var i = 0, l = my_textareas.length; i < l; ++i) $(result_textareas[i]).val($(my_textareas[i]).val());
            for (var i = 0, l = my_selects.length;   i < l; ++i) result_selects[i].selectedIndex = my_selects[i].selectedIndex;

            return result;
        };
        }) (jQuery.fn.clone);
            jQuery(document).ready(function() {

                jQuery('.ts-repeater-addmore').click(function(){
                    jQuery(this).parent().after(jQuery(this).parent().clone());
                    jQuery(this).parent().find('input,select').val('');
                    //console.log(jQuery(this).parent().parent().find('div.one_item_container:eq(1) a.sw-add'));
                    jQuery(this).parent().find('.ts-settings-upl').html('Upload Image');
                    jQuery(this).parent().find('.ts-settings-rmv').hide().next().val('');
                    jQuery(this).parent().parent().find('div.ts-repeater-field:eq(1) a.ts-repeater-addmore')
                                        .addClass('ts-repeater-remove').removeClass('ts-repeater-addmore');

                    sw_reload_events();

                    return false;
                });

                sw_reload_events();
            });

            function sw_reload_events()
            {
                jQuery('a.ts-repeater-remove').unbind();

                jQuery('a.ts-repeater-remove').click(function(){
                    jQuery(this).parent().remove();
                    sw_refresh_value();
                    return false;
                });

                jQuery('div.ts-repeater-container .ts-repeater-field input').change(function(){
                    sw_refresh_value();
                });

                sw_refresh_value();
            }

            function sw_refresh_value()
            {
                var obj_array = [];
                var pos_id = 0;
                jQuery.each(jQuery('div.ts-repeater-container .ts-repeater-field'), function( key, value ) {
                    var tmp=[];
                    jQuery(this).find('select,input,textarea').each(function(i,$ip){
                        //console.log(($ip.value))
                        if(jQuery($ip).val()!==''){
                            tmp.push(jQuery($ip).attr('name').replace(/[\[\]']+/g,'')+':'+jQuery($ip).val());
                        }else{
                            tmp=[];
                        }
                    });
                    if(tmp.length>0) obj_array.push(tmp);
                });
                var generate_json = JSON.stringify(obj_array);
                //var generate_json = '['+obj_array.join(",\n")+']';
                jQuery('#<?php echo $parent_id?>').text(generate_json);
            }
        </script>
<?php
    }
  
    private function media_field($field){
        $value = !isset($field['value'])?get_option( $field['id'] ):$field['value'];
        $name=isset($field['name'])?$field['name']:$field['id'];
        if( $image = wp_get_attachment_image_src( $value ) ) {

            echo '<a href="#" class="ts-settings-upl"><img src="' . $image[0] . '" /></a>
                <a href="#" class="ts-settings-rmv">Remove image</a>
                <input type="hidden" name="'.$name.'" value="' . $value . '">';

        } else {

            echo '<a href="#" class="ts-settings-upl">Upload image</a>
                <a href="#" class="ts-settings-rmv" style="display:none">Remove image</a>
                <input type="hidden" name="'.$name.'" value="">';

        }
    }
    public static function get_settings(){
        $sets=[];
        //var_dump(self::$allFields);
        foreach(self::$allFields as $fld){
            if($fld['type']=='repeater'){
                $main_value=json_decode(get_option( $fld['id'] ));
                $main_value=(array_map(array(__CLASS__,'formt'),$main_value));
                $main_value=array_map(array(__CLASS__,'flt'),$main_value);
                $sets[$fld['id']]=$main_value;
            }else{
                $sets[$fld['id']]=get_option($fld['id']);
            }

        }
        return $sets;
    }
  }