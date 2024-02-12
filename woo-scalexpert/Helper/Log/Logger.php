<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for WordPress.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

namespace wooScalexpert\Helper\Log;

use Exception;
use Monolog;

class LoggerHelper
{
    public Monolog\Logger $logger;

    public int $activeLog = 0;

    /**
     * @throws Exception
     */
    public function __construct()
	{
        $this->logger            = new \Monolog\Logger('log');
        $this->logger->pushHandler(new Monolog\Handler\StreamHandler(PLUGIN_DIR . '/logs/'.date('Ymd').'.log', Monolog\Logger::INFO));

        $this->activeLog = is_array(get_option('sg_scalexpert_debug')) ? (int) get_option('sg_scalexpert_debug')['mode_debug'] : 0;
	}

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
	public function logInfo(string $message, array $context = []): void
	{
        if ($this->activeLog) {
            $this->logger->log(Monolog\Logger::INFO, $message, $context);
        }
	}

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
	public function logError(string $message, array $context = []): void
	{
        if ($this->activeLog) {
            $this->logger->log(Monolog\Logger::ERROR, $message, $context);
        }
	}
}
