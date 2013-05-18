<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


    <?php echo get_config('company.name'); ?> has created an online account for you to track your project's status.

    <br>
    <br>

    You can access your account with the following information:

    <br>
    <br>

    <strong>url:</strong> <a style="color:#4e79c6;" href=<?php echo get_config('base_url'); ?>><?php echo get_config('base_url'); ?></a><br>
    <strong>email:</strong> <?php echo $email;?><br>
    <strong>passsword:</strong> <?php echo $temporary_password;?>


    <br>
    <br>



<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>