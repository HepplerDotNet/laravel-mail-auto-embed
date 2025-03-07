<?php

namespace HepplerDotNet\LaravelMailAutoEmbed\Embedder;

use Exception;
use HepplerDotNet\LaravelMailAutoEmbed\Models\EmbeddableEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class AttachmentEmbedder extends Embedder
{
    /**
     * @var Email
     */
    protected $symfonyMessage;

    /**
     * @return AttachmentEmbedder
     *
     * @throws Exception
     */
    public function setSymfonyMessage(Email $message)
    {
        $this->symfonyMessage = $message;

        return $this;
    }

    /**
     * @param string $url
     *
     * @throws Exception
     */
    public function fromUrl($url)
    {
        $localFile = str_replace(url('/'), public_path(), $url);

        if (file_exists($localFile)) {
            return $this->fromPath($localFile);
        }

        if ($embeddedFromRemoteUrl = $this->fromRemoteUrl($url)) {
            return $embeddedFromRemoteUrl;
        }

        return $url;
    }

    /**
     * @param $path
     *
     * @return string
     *
     * @throws Exception
     */
    public function fromPath($path)
    {
        return $this->embed(file_get_contents($path), basename($path), mime_content_type($path));
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function fromEntity(EmbeddableEntity $entity)
    {
        return $this->embed($entity->getRawContent(), $entity->getFileName(), $entity->getMimeType());
    }

    /**
     * @param string $url
     *
     * @throws Exception
     */
    public function fromRemoteUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $hashName = implode('_', [
                'laravel-mail-auto-embed',
                hash('sha256', $url),
            ]);

            if (config('mail-auto-embed.curl.cache', false) && $file = Cache::get($hashName)) {
                return $this->embed($file['content'], $file['name'], $file['type']);
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $raw = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpcode == 200) {
                $pathInfo = pathinfo($url);

                $queryStr = parse_url($url, PHP_URL_QUERY) ?: '';
                parse_str($queryStr ?? '', $queryParams);
                $basename = $queryParams['basename'] ?? $pathInfo['basename'];

                if (config('mail-auto-embed.curl.cache', false)) {
                    Cache::put($hashName, [
                        'content' => $raw,
                        'name' => $basename,
                        'type' => $contentType,
                    ], config('mail-auto-embed.curl.cache_ttl', 3600));
                }

                return $this->embed($raw, $basename, $contentType);
            }
        }

        return $url;
    }

    /**
     * @param $body
     * @param $name
     * @param $type
     *
     * @return string
     *
     * @throws Exception
     */
    protected function embed($body, $name, $type)
    {
        if (!empty($this->symfonyMessage)) {
            if (gettype($name) !== 'string') {
                $name = Str::random();
            }
            $this->symfonyMessage->embed($body, $name, $type);

            return "cid:$name";
        }

        throw new Exception('No message defined');
    }
}
