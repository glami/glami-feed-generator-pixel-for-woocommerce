<?php
class Glami_Feed_Generator_Pixel_For_Woocommerce_CLI extends WP_CLI_Command
{
    public function generate($args, $assoc_args)
    {
        WP_CLI::log('Glami_Feed is generating...');
        $feed = new Glami_Feed_Generator_Pixel_For_Woocommerce_Engine();
        $feed->generate();
        WP_CLI::log('Glami_Feed generation has finished.');
    }
}