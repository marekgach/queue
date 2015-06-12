<?php
namespace Kurzor\Tools\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

class EntityManagerHelper extends Helper
{
    /**
     * Doctrine ORM EntityManagerInterface.
     *
     * @var EntityManagerInterface
     */
    protected $_em;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct($em)
    {
        $this->_em = $em;
    }

    /**
     * Retrieves Doctrine ORM EntityManager.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'entityManager';
    }
}