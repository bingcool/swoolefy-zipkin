<?php
namespace zipkin;

use whitemerry\phpkin\Tracer;
use whitemerry\phpkin\TracerInfo;

class ZipkinTracer extends Tracer {

	/**
     * Save trace
     */
    public function trace($is_async = false)
    {
        if (!TracerInfo::isSampled()) {
            return;
        }

        $unsetParentId = true;
        if ($this->profile === static::BACKEND && !$this->unsetParentIdForBackend) {
            $unsetParentId = false;
        }

        $this->addTraceSpan($unsetParentId);

        if($is_async) {
        	$this->logger->asyncTrace($this->spans);
        }else {
        	$this->logger->trace($this->spans);
        }
    }

}