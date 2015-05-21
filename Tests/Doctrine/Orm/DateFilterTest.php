<?php

namespace Dunglas\ApiBundle\Tests\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\ApiBundle\Doctrine\Orm\DateFilter;
use Doctrine\ORM\EntityRepository;
use Dunglas\ApiBundle\Api\Resource;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DateFilterTest.
 *
 * @@coversDefaultClass Dunglas\ApiBundle\Doctrine\Orm\DateFilter
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class DateFilterTest extends KernelTestCase
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        $class                 = 'Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy';
        $manager               = DoctrineTestHelper::createTestEntityManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository      = $manager->getRepository($class);
        $this->resource        = new Resource($class);
    }

    /**
     * @covers ::apply
     *
     * @dataProvider filterProvider
     */
    public function testApply(array $filterParameters, array $query, $expected)
    {
        $request      = Request::create('/api/dummies', 'GET', $query);
        $queryBuilder = $this->getQueryBuilder();
        $filter       = new DateFilter(
            $this->managerRegistry,
            $filterParameters['properties']
        );

        $filter->apply($this->resource, $queryBuilder, $request);
        $actual   = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower($expected);

        $this->assertEquals(
            $expected,
            $actual,
            sprintf('Expected `%s` for this `%s %s` request', $expected, 'GET', $request->getUri())
        );
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder QueryBuilder for filters.
     */
    public function getQueryBuilder()
    {
        return $this->repository->createQueryBuilder('o');
    }

    /**
     * Providers 3 parameters:
     *  - filter parameters.
     *  - properties to test. Keys are the property name. If the value is true, the filter should work on the property,
     *    otherwise not.
     *  - expected DQL query
     *
     * @return array
     */
    public function filterProvider()
    {
        return [
            // Properties enabled with valid values
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05'
                    ]
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o where o.dummydate >= :afterdummydate'
            ],
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyDate' => [
                        'after'  => '2015-04-05',
                        'before' => '2015-04-05'
                    ]
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o where o.dummydate >= :afterdummydate and o.dummydate <= :beforedummydate'
            ],
            [
                [
                    'properties' => ['unkown'],
                ],
                [
                    'dummyDate' => [
                        'after'  => '2015-04-05',
                        'before' => '2015-04-05'
                    ]
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o'
            ]
        ];
    }
}
