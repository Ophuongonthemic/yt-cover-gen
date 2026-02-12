<?php

namespace Artryazanov\YtCoverGen\Generators;

use Artryazanov\YtCoverGen\Contracts\CoverGeneratorInterface;
use Artryazanov\YtCoverGen\Exceptions\GeminiResponseException;
use Throwable;

class FallbackCoverGenerator implements CoverGeneratorInterface
{
    private CoverGeneratorInterface $primary;
    private CoverGeneratorInterface $fallback;
    /** @var callable|null */
    private $errorHandler;

    public function __construct(
        CoverGeneratorInterface $primary, 
        CoverGeneratorInterface $fallback,
        ?callable $errorHandler = null
    ) {
        $this->primary = $primary;
        $this->fallback = $fallback;
        $this->errorHandler = $errorHandler;
    }

    public function generate(string $imagePath, string $gameName, string $videoDescription): string
    {
        try {
            return $this->primary->generate($imagePath, $gameName, $videoDescription);
        } catch (GeminiResponseException $e) {
            // Force fallback for Gemini response errors, ignoring error handler re-throws if necessary
            if ($this->errorHandler) {
                try {
                    call_user_func($this->errorHandler, $e);
                } catch (Throwable $handlerException) {
                    // Suppress handler exception to ensure fallback
                }
            }
        } catch (Throwable $e) {
            if ($this->errorHandler) {
                call_user_func($this->errorHandler, $e);
            }
        }

        return $this->fallback->generate($imagePath, $gameName, $videoDescription);
    }
}
