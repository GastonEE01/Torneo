<?php

class MustachePresenter{
    private $mustache;
    private $partialsPathLoader;

    public function __construct($partialsPathLoader){
        Mustache_Autoloader::register();
        $this->mustache = new Mustache_Engine(
            array(
                'partials_loader' => new Mustache_Loader_FilesystemLoader( $partialsPathLoader )
            ));
        $this->partialsPathLoader = $partialsPathLoader;
    }

    public function render($contentFile, $data = array(), $options = []) {
        echo $this->generateHtml($contentFile, $data, $options);
    }

    public function generateHtml($contentFile, $data = array(), $options = []) {
        // Configuración por defecto: incluir header, footer y sidebar
        $defaultOptions = [
            'includeHeader' => false,
            'includeFooter' => false,
            'includeSidebar' => false,
        ];

        // Mezcla las opciones por defecto con las opciones específicas
        $options = array_merge($defaultOptions, $options);

        $contentAsString = '';

        // Incluir header si está habilitado
        if ($options['includeHeader']) {
            $contentAsString .= file_get_contents($this->partialsPathLoader . '/header.mustache');
        }

        // Incluir sidebar/navbar si está habilitado
        if ($options['includeSidebar']) {
            $contentAsString .= file_get_contents($this->partialsPathLoader . '/sidebar.mustache');
        }

        // Incluir contenido principal
        if (isset($contentFile)) {
            $contentAsString .= file_get_contents($contentFile);
        }

        // Incluir footer si está habilitado
        if ($options['includeFooter']) {
            $contentAsString .= file_get_contents($this->partialsPathLoader . '/footer.mustache');
        }

        return $this->mustache->render($contentAsString, $data);
    }

}
