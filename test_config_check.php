<?php
    header('Content-Type: application/json');
    
    // Check which config is being loaded
    $configPath = __DIR__ . '/config/config.php';
    $prodConfigPath = __DIR__ . '/config/config.production.php';
    
    $result = [
      'config_exists' => file_exists($configPath),
      'prod_config_exists' => file_exists($prodConfigPath),
      'env_vars' => [
        'DB_HOST' => getenv('DB_HOST'),
        'DB_NAME' => getenv('DB_NAME'),
        'DB_USER' => getenv('DB_USER'),
        'DB_PASSWORD' => getenv('DB_PASSWORD') ? '***set***' : false
      ]
    ];
    
    if (file_exists($configPath)) {
      $config = require $configPath;
      $result['loaded_config'] = [
        'dbname' => $config['database']['dbname'],
        'username' => $config['database']['username']
      ];
    }
    
    if (file_exists($prodConfigPath) && !getenv('DB_NAME')) {
      $prodConfig = require $prodConfigPath;
      $result['prod_config_fallback'] = [
        'dbname' => $prodConfig['database']['dbname'],
        'username' => $prodConfig['database']['username']
      ];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    ?>