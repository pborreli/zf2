<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Serializer
 */

namespace Zend\Serializer\Adapter;

use Zend\Serializer\Exception;
use Zend\Stdlib\ErrorHandler;

/**
 * @link       http://www.infoloom.com/gcaconfs/WEB/chicago98/simeonov.HTM
 * @link       http://en.wikipedia.org/wiki/WDDX
 * @category   Zend
 * @package    Zend_Serializer
 * @subpackage Adapter
 */
class Wddx extends AbstractAdapter
{
    /**
     * @var WddxOptions
     */
    protected $options = null;

    /**
     * Constructor
     *
     * @param  array|\Traversable|WddxOptions $options
     * @throws Exception\ExtensionNotLoadedException if wddx extension not found
     */
    public function __construct($options = null)
    {
        if (!extension_loaded('wddx')) {
            throw new Exception\ExtensionNotLoadedException(
                'PHP extension "wddx" is required for this adapter'
            );
        }

        parent::__construct($options);
    }

    /**
     * Set options
     *
     * @param  array|\Traversable|WddxOptions $options
     * @return Wddx
     */
    public function setOptions($options)
    {
        if (!$options instanceof WddxOptions) {
            $options = new WddxOptions($options);
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Get options
     *
     * @return WddxOptions
     */
    public function getOptions()
    {
        if ($this->options === null) {
            $this->options = new WddxOptions();
        }
        return $this->options;
    }

    /**
     * Serialize PHP to WDDX
     *
     * @param  mixed $value
     * @return string
     * @throws Exception\RuntimeException on wddx error
     */
    public function serialize($value)
    {
        $comment = $this->getOptions()->getComment();

        ErrorHandler::start();
        if ($comment !== '') {
            $wddx = wddx_serialize_value($value, $comment);
        } else {
            $wddx = wddx_serialize_value($value);
        }
        $error = ErrorHandler::stop();

        if ($wddx === false) {
            throw new Exception\RuntimeException('Serialization failed', 0, $error);
        }

        return $wddx;
    }

    /**
     * Unserialize from WDDX to PHP
     *
     * @param  string $wddx
     * @return mixed
     * @throws Exception\RuntimeException on wddx error
     */
    public function unserialize($wddx)
    {
        $ret = wddx_deserialize($wddx);

        if ($ret === null && class_exists('SimpleXMLElement', false)) {
            // check if the returned NULL is valid
            // or based on an invalid wddx string
            try {
                libxml_disable_entity_loader(true);
                $simpleXml = new \SimpleXMLElement($wddx);
                libxml_disable_entity_loader(false);
                if (isset($simpleXml->data[0]->null[0])) {
                    return null; // valid null
                }
                throw new Exception\RuntimeException('Unserialization failed: Invalid wddx packet');
            } catch (\Exception $e) {
                throw new Exception\RuntimeException('Unserialization failed: ' . $e->getMessage(), 0, $e);
            }
        }

        return $ret;
    }
}
