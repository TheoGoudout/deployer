<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Ftp;

use Deployer\Deployer;
use Deployer\Exception\RuntimeException;
use Deployer\Host\Host;
use Deployer\Utility\ProcessOutputPrinter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Client
{
    /**
     * @var string
     */
    private $tmpdirs;

    public function __construct()
    {
        $this->tmpdirs = array();
    }

    public function __destruct() {
        // TODO: copy files over FTP
        foreach ($this->tmpdirs as $hostname => $tmpdir) {
            Magic::unmount($hostname);
            unlink($tmpdir);
        }
    }

    /**
     * @param Host $host
     * @param string $command
     * @param array $config
     * @return string
     * @throws RuntimeException
     */
    public function run(Host $host, string $command, array $config = [])
    {
        $hostname = $host->getHostname();
        if (!isset($this->tmpdirs[$hostname])) {
            $tmpdir = self::$tmpdir();
            if ($tmpdir === null) {
                throw new \Exception("Unable to create temporary directory for host $hostname");
            }
            $deploy_path = $host->get('deploy_path');
            $driver = $host->get('use_ssl', false) ? 'ftps' : 'ftp';
            Magic::mount($hostname, $driver,
                [
                'host' => $host->getRealHostname(),
                'username' => $host->getUser(),
                'password' => $host->get('password'),
                'directory' => $deploy_path
                ]);

            $this->tmpdirs[$hostname] = $tmpdir;
            $host->set('deploy_path', $tmpdir . $deploy_path);
        }

        $process = Deployer::get()->processRunner;
        $output = $process->run($hostname, $command, $options);

        return $output;
    }

    /**
     * @return string
     */
    private static function tmpdir() {
        $tmpfile=tempnam(sys_get_temp_dir(),'');
        
        if (file_exists($tmpfile)) {
            unlink($tmpfile);
        }

        mkdir($tmpfile);
        if (is_dir($tmpfile)) {
            return $tmpfile;
        }
    }
}
