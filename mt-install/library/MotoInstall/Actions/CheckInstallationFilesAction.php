<?php
namespace MotoInstall\Actions;

use MotoInstall;

class CheckInstallationFilesAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $absoluteTempPath = MotoInstall\System::getAbsolutePath('@installationFilesDir');
        $relativeTempPath = MotoInstall\System::getRelativePath('@installationFilesDir');
        $websitePath = MotoInstall\System::getAbsolutePath('@website');

        if (!is_dir($absoluteTempPath)) {
            throw new MotoInstall\Exception('CHECKING_FAILED', 400, null, array(
                'dir_not_exists' => $relativeTempPath,
            ));
        }

        $errors = array(
            'notReadable' => array(),
            'notWritable' => array(),
            'notCreatable' => array(),
            'notFile' => array(),
            'notDir' => array(),
        );

        $report = array(
            'files' => 0,
            'dirs' => 0,
        );

        $files = MotoInstall\Util::scanDir($absoluteTempPath, '', array(
            'addDir' => true,
        ));
        $installationRequirements = new MotoInstall\DataBag(MotoInstall\System::config('installationRequirements'));

        if ($installationRequirements->has('minFiles') && count($files) < $installationRequirements->get('minFiles')) {
            throw new MotoInstall\Exception('Too few files', 400, null, array(
                'current' => count($files),
                'requirements' => $installationRequirements->get('minFiles'),
            ));
        }

        $requiredFiles = (array) $installationRequirements->get('files');
        $requiredFiles = array_diff($requiredFiles, $files);
        $requiredFiles = array_values($requiredFiles);

        $requiredDirs = (array) $installationRequirements->get('dirs');
        $requiredDirs = array_diff($requiredDirs, $files);
        $requiredDirs = array_values($requiredDirs);

        foreach ($files as $filePath) {
            $from = $absoluteTempPath . '/' . $filePath;
            $to = $websitePath . '/' . $filePath;
            if (is_file($from)) {
                $report['files']++;
                if (MotoInstall\Tester::isFailed('check:is_readable') || !is_readable($from)) {
                    $errors['notReadable'][] = $relativeTempPath . '/' . $filePath;
                }
                if (file_exists($to)) {
                    if (MotoInstall\Tester::isFailed('check:is_file') || !is_file($to)) {
                        $errors['notFile'][] = $filePath;
                        continue;
                    }
                    if (MotoInstall\Tester::isFailed('check:is_writable') || !is_writable($to)) {
                        $errors['notWritable'][] = $filePath;
                        continue;
                    }
                } else {
                    $toDir = dirname($to);
                    if (is_dir($toDir)) {
                        if (MotoInstall\Tester::isFailed('check:is_writable') || !is_writable($toDir)) {
                            $errors['notWritable'][] = dirname($filePath);
                            continue;
                        }
                    }
                }
            } elseif (is_dir($from)) {
                $report['dirs']++;
                if (file_exists($to) && (MotoInstall\Tester::isFailed('check:is_dir') || !is_dir($to))) {
                    $errors['notDir'][] = $filePath;
                    continue;
                }
            }

        }

        if (count($errors['notReadable']) === 0) {
            unset($errors['notReadable']);
        }
        if (count($errors['notWritable']) === 0) {
            unset($errors['notWritable']);
        }
        if (count($errors['notCreatable']) === 0) {
            unset($errors['notCreatable']);
        }
        if (count($errors['notFile']) === 0) {
            unset($errors['notFile']);
        }
        if (count($errors['notDir']) === 0) {
            unset($errors['notDir']);
        }

        if (count($requiredFiles) > 0) {
            $errors['requiredFiles'] = $requiredFiles;
        }
        if (count($requiredDirs) > 0) {
            $errors['requiredDirs'] = $requiredDirs;
        }

        if (!empty($errors)) {
            throw new MotoInstall\Exception('CHECKING_FAILED', 400, null, $errors);
        }

        return array(
            'report' => MotoInstall\Util::arrayOnly($report, array('files', 'dirs')),
        );
    }
}
