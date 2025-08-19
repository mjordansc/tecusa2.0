<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

class Menu
{

    public static function oct8ne_page_settings()
    {

        if (!Oct8neLoginHelper::isLogged()) {

            //$result = $_GET['lresult'];
            if (isset($_GET['lresult']) && $_GET['lresult'] == 2) {

                ?>

                <div class="error notice">
                    <p>Error al iniciar sesion</p>
                </div>

                <?php
            } else if (isset($_GET['lresult']) && $_GET['lresult'] == 1) {


                ?>
                <div class="updated notice">
                    <p>Desconectado correctamente</p>
                </div>
                <?php

            }
            ?>
            <div class="wrap">
                <h2><?php echo __('Oct8ne plugin configuration', 'oct8ne') ?></h2>
                <h3><?php echo __('Login', 'oct8ne') ?></h3>
                <br>
                <form method='POST' action='<?php echo plugins_url() ?>/oct8ne/actions/oct8neconnector/?octmethod=linkup'>
                    <?php
                    settings_fields('oct8ne-settings-grpups');
                    do_settings_sections('oct8ne-settings-grpups');
                    ?>
                    <table>
                        <tr>
                            <th><label for="oct8ne_value">Email</label></th>
                            <td>
                                <input type='text'
                                       name='oct8ne_value'
                                       id='oct8ne_value'
                                       class="regular-text"/>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="oct8ne_password">Password</label></th>

                            <td>
                                <input type='password'
                                       name='oct8ne_password'
                                       id='oct8ne_password'
                                       class="regular-text"/>
                            </td>
                        </tr>

                        <tr>

                            <td></td>
                            <td>
                                <?php submit_button(); ?>
                            </td>
                        </tr>


                    </table>
                </form>

            </div>

            <?php
        } else {

           // $result = $_GET['lresult'];
            if (isset($_GET['lresult']) && $_GET['lresult'] == 3) {

                ?>

                <div class="updated notice">
                    <p>Sesión iniciada correctamente</p>
                </div>

                <?php
            }else if ($_GET['lresult'] == 4) {

                ?>

                <div class="updated notice">
                    <p>Configuracion cargada correctamente</p>
                </div>

                <?php
            }
            ?>

            <div class="wrap">
                <h2><?php echo __('Oct8ne plugin configuration', 'oct8ne') ?></h2>
                <br>


                <form method='POST' action='<?php echo plugins_url() ?>/oct8ne/actions/oct8neconnector/?octmethod=changeConfiguration'>

                    <h1>Configuration</h1>
                    <br>
                    <table>
                        <tr>
                            <th><label for="oct8ne_value">Javascript Position: </label></th>
                            <td>
                                <select style="width: 200px" name="oct8ne_js_position">

                                    <?php foreach (Oct8neJsInfo::getPositions() as $key => $value){
                                        ?>
                                        <option value="<?php echo $key ?>" <?php if($value) echo "selected"?> > <?php echo $key ?></option>
                                    <?php } ?>

                                </select>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <th><label for="oct8ne_value">Search Engine: </label></th>
                            <td>
                                <select style="width: 200px" name="oct8ne_search_engine">
                                    <?php foreach (Oct8neSearchFactory::getEngines() as $key => $value){
                                        ?>
                                        <option value="<?php echo $key ?>" <?php if($value) echo "selected"?> > <?php echo $key ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>

                        <br>
                        <tr>
                            <th><label for="oct8ne_value">WishList Engine: </label></th>
                            <td>
                                <select style="width: 200px" name="oct8ne_wishlist_engine">
                                    <?php foreach (WishListFactory::getEngines() as $key => $value){
                                        ?>
                                        <option value="<?php echo $key ?>" <?php if($value) echo "selected"?> > <?php echo $key ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="oct8ne_value">Translation Engine: </label></th>
                            <td>
                                <select style="width: 200px" name="oct8ne_translation_engine">
                                    <?php foreach (Oct8neTranslationFactory::getEngines() as $key => $value){
                                        ?>
                                        <option value="<?php echo $key ?>" <?php if($value) echo "selected"?> > <?php echo $key ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="oct8ne_value">Additional script: </label></th>
                            <td>
                                    <textarea style="width: 400px;height: 100px;" name="oct8ne_script_extra"><?php echo Oct8neLoginHelper::getExtraScript() ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                <label for="oct8ne_value"> Delay Oct8ne loading until user interaction: </label>
                            </th>
                            <td>
                            <?php $scriptEvents = Oct8neLoginHelper::getScriptEvents()?>
                                <select style="width: 200px" name="oct8ne_script_events">                                    
                                    <option value="DISABLED" <?php if($scriptEvents == '' || $scriptEvents == 'DISABLED') echo "selected"?>>Disabled</option>
                                    <option value="ALL" <?php if($scriptEvents == 'ALL') echo "selected"?> >Any user event (Click, Scroll, Mousemove)</option>                                    
                                    <option value="scroll" <?php if($scriptEvents == 'scroll') echo "selected"?> >Scroll</option>                                    
                                    <option value="click" <?php if($scriptEvents == 'click') echo "selected"?> >Click</option>                                    
                                    <option value="mousemove" <?php if($scriptEvents == 'mousemove') echo "selected"?> >Mousemove</option>
                                    <option value="SCRIPT" <?php if($scriptEvents == 'SCRIPT') echo "selected"?> >Script call</option>
                                </select>   
                                <span style="font-size: 11px">**If you choose "Script call" you must call the function <i>insertOct8ne();</i> whenever you want load Oct8ne's code on the page.**</span>

                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="oct8ne_value">Delay Oct8ne loading on page (seconds):</label></th>
                            <td>
                            <?php $scriptTimer = Oct8neLoginHelper::getScriptTimer()?>
                                <select style="width: 200px" name="oct8ne_script_timer">                                    
                                    <option value="DISABLED" <?php if($scriptTimer == '' || $scriptTimer == 'DISABLED') echo "selected"?>>Disabled</option>
                                    <option value="1" <?php if($scriptTimer == '1') echo "selected"?>>1</option>
                                    <option value="2" <?php if($scriptTimer == '2') echo "selected"?>>2</option>
                                    <option value="3" <?php if($scriptTimer == '3') echo "selected"?>>3</option>
                                    <option value="4" <?php if($scriptTimer == '4') echo "selected"?>>4</option>
                                    <option value="5" <?php if($scriptTimer == '5') echo "selected"?>>5</option>
                                    <option value="6" <?php if($scriptTimer == '6') echo "selected"?>>6</option>
                                    <option value="7" <?php if($scriptTimer == '7') echo "selected"?>>7</option>
                                    <option value="8" <?php if($scriptTimer == '8') echo "selected"?>>8</option>
                                    <option value="9" <?php if($scriptTimer == '9') echo "selected"?>>9</option>
                                    <option value="10" <?php if($scriptTimer == '10') echo "selected"?>>10</option>                                                                     
                                </select>
                            </td>
                        </tr>

                        <tr>

                            <td>
                                <?php submit_button('Save configuration'); ?>
                            </td>
                        </tr>

                    </table>
                </form>

                <form method='POST' action='<?php echo plugins_url() ?>/oct8ne/actions/oct8neconnector/?octmethod=unlink''>

                    <h1>Session</h1>
                    <br>
                    <table>
                        <tr>
                            <th><label for="oct8ne_value">Email: </label></th>
                            <td>
                                <span id='oct8ne_value'> <?php echo Oct8neLoginHelper::getUserEmail() ?></span>
                            </td>
                        </tr>

                        <tr>

                            <td>
                                <?php submit_button('Logout'); ?>
                            </td>
                        </tr>

                    </table>
                </form>

            </div>


            <?php


        }
    }
}