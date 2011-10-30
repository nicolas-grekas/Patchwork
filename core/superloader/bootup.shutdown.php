<?php // vi: set fenc=utf-8 ts=4 sw=4 et:

Patchwork_ShutdownHandler::setup();
Patchwork\FunctionOverride(register_shutdown_function, Patchwork_ShutdownHandler::register, $callback);
