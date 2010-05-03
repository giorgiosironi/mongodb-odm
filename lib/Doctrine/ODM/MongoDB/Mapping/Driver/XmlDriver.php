<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @license 	http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    	www.doctrine-project.org
 * @since   	1.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class XmlDriver extends AbstractFileDriver
{
    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $_fileExtension = '.dcm.xml';

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $xmlRoot = $this->getElement($className);
        if ( ! $xmlRoot) {
            return;
        }


        if (isset($xmlRoot['db'])) {
            $class->setDB((string) $xmlRoot['db']);
        }
        if (isset($xmlRoot['collection'])) {
            $class->setCollection((string) $xmlRoot['collection']);
        }
        if (isset($xmlRoot['repository-class'])) {
            $class->setCustomRepositoryClass((string) $xmlRoot['repository-class']);
        }
        if (isset($xmlRoot['indexes'])) {
            foreach($xmlRoot['indexes'] as $index) {
                $class->addIndex((array) $index['keys'], (array) $index['options']);
            }
        }
        if (isset($xmlRoot['inheritance-type'])) {
            $inheritanceType = (string) $xmlRoot['inheritance-type'];
            $class->setInheritanceType(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceType));
        }
        if (isset($xmlRoot->{'discriminator-field'})) {
            $discrField = $xmlRoot->{'discriminator-field'};
            $class->setDiscriminatorField(array(
                'name' => (string) $discrField['name'],
                'fieldName' => (string) $discrField['fieldName'],
            ));
        }
        if (isset($xmlRoot->{'discriminator-map'})) {
            $map = array();
            foreach ($xmlRoot->{'discriminator-map'}->{'discriminator-mapping'} AS $discrMapElement) {
                $map[(string) $discrMapElement['value']] = (string) $discrMapElement['class'];
            }
            $class->setDiscriminatorMap($map);
        }
        if (isset($xmlRoot->inheritance['type'])) {
            $class->discriminatorMap = $xmlRoot['inheritance'];
        }
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $field) {
                $mapping = array();
                $attributes = $field->attributes();
                foreach ($attributes as $key => $value) {
                    $mapping[$key] = (string) $value;
                }
                $class->mapField($mapping);
            }
        }
        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $class->addLifecycleCallback((string) $lifecycleCallback['method'], constant('Doctrine\ORM\Events::' . (string) $lifecycleCallback['type']));
            }
        }
    }

    protected function _loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->document)) {
            foreach ($xmlElement->document as $documentElement) {
                $documentName = (string) $documentElement['name'];
                $result[$documentName] = $documentElement;
            }
        }

        return $result;
    }
}