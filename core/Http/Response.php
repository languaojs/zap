<?php

namespace Zap\Core\Http;

class Response
{
    protected int $status = 200;
    protected array $headers = [];
    protected string $content = '';

    public function status(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public static function make(string $content = '', int $status = 200): static
    {
        return (new static)
            ->content($content)
            ->status($status);
    }

    public static function json(array $data, int $status = 200): static
    {
        return (new static)
            ->header('Content-Type', 'application/json')
            ->content(json_encode($data))
            ->status($status);
    }

    public static function xml(array $data, int $status = 200, string $root = 'response'): static
    {
        $xml = new \SimpleXMLElement("<{$root}/>");

        self::arrayToXml($data, $xml);

        return (new static)
            ->header('Content-Type', 'application/xml')
            ->content($xml->asXML())
            ->status($status);
    }

    protected static function arrayToXml(array $data, \SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = "item";
            }

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    public static function redirect(string $url): static
    {
        return (new static)
            ->status(302)
            ->header('Location', $url);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $this->content;
    }
}
