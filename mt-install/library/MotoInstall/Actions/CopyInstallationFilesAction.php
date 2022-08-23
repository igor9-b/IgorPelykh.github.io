<?php
namespace MotoInstall\Actions;

use MotoInstall;

class CopyInstallationFilesAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $absoluteTempPath = MotoInstall\System::getAbsolutePath('@installationFilesDir');
        $relativeTempPath = MotoInstall\System::getRelativePath('@installationFilesDir');
        $websitePath = MotoInstall\System::getAbsolutePath('@website');

        if (!is_dir($absoluteTempPath)) {
            throw new MotoInstall\Exception('COPING_FAILED', 400, null, array(
                'dir_not_exists' => $relativeTempPath,
            ));
        }

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
        if (count($requiredFiles) > 0) {
            $errors['requiredFiles'] = array_values($requiredFiles);
        }
        if (count($requiredDirs) > 0) {
            $errors['requiredDirs'] = array_values($requiredDirs);
        }
        if (!empty($errors)) {
            throw new MotoInstall\Exception('CHECKING_FAILED', 400, null, $errors);
        }

        $errors = array(
            'createDir' => array(),
            'copyFiles' => array(),
        );

        $report = array(
            'files' => 0,
            'dirs' => 0,
        );

        foreach ($files as $filePath) {
            $from = $absoluteTempPath . '/' . $filePath;
            $to = $websitePath . '/' . $filePath;
            if (is_dir($from)) {
                $report['dirs']++;
                if (is_dir($to)) {
                    continue;
                }
                if (MotoInstall\Tester::isFailed('copy:create_dir') || !MotoInstall\Util::createDir($to)) {
                    $errors['createDir'][] = $filePath;
                }
            } else {
                $report['files']++;
                if (MotoInstall\Tester::isFailed('copy:copy_file') || !MotoInstall\Util::copyFile($from, $to, true)) {
                    $errors['copyFiles'][] = $filePath;
                }
            }
        }
        if (count($errors['createDir']) || count($errors['copyFiles'])) {
            throw new MotoInstall\Exception('COPYING_FAILED', 400, null, $errors);
        }

        return array(
            'report' => $report,
        );
    }
}
