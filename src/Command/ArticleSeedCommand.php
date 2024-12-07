<?php

namespace App\Command;

use App\Entity\Article;
use Faker\Factory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:ArticleSeed',
    description: 'Populate article table',
)]
class ArticleSeedCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    // protected function configure(): void
    // {
    //     $this
    //         ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
    //         ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
    //     ;
    // }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();
        $name = ['john doe', 'adam wills', 'blake riley', 'thomas jefferson'];
        for ($i = 0; $i <= 100; $i++) {
            $article = new Article();
            $article->setAuthorName($faker->randomElement($name));
            $article->setTitle($faker->sentence(4));
            $article->setSummary($faker->paragraph(10));
            $article->setImage($faker->imageUrl(800, 400, 'business', true, 'Blog Image'));
            $article->setCreatedAt($faker->dateTimeThisYear);

            $this->em->persist($article);
        }

        $this->em->flush();

        $output->writeln('populated article table with 100 records');

        return command::SUCCESS;
    }
}
