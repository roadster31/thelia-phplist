<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 21/07/2016 09:14
 */

namespace PhpList\Command;

use PhpList\PhpList;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

class Sync extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("phplist:sync")
            ->setDescription("Synchronize local newsletter subscribers with phpList subscribers")
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Synchronizing...</info>");
        
        $this->getDispatcher()->dispatch(PhpList::RESYNC_EVENT);
    
        $output->writeln("<info>Synchronization done...</info>");
    
    }
}
