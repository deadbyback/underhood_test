<?php
namespace App\Storage;

use App\Models\Model;

/**
 * Class FileStorage
 * @package App\Storage
 *
 * Handles storage operations in a filesystem directory.
 */
class FileStorage implements Storage
{
    /**
     * @var string The directory path where files are stored.
     */
    private string $dirPath;

    /**
     * FileStorage constructor.
     *
     * Initializes the directory path for storage.
     */
    public function __construct()
    {
        $this->dirPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'filestorage' . DIRECTORY_SEPARATOR;
    }

    /**
     * Saves the Model instance data to a file.
     *
     * @param Model $input The model instance containing data to be saved.
     * @param string $sourceName The base name for the file.
     * @param array $params Other params.
     *
     * @return bool True if the file is successfully saved, false otherwise.
     */
    public function save(Model $input, string $sourceName, array $params = []): bool
    {
        try {
            if (isset($params['suffix'])) {
                $sourceName .= '_' . $params['suffix'];
            }
            $fileName = $this->generateFileName($input, $sourceName);
            $filePath = $this->dirPath . $fileName;

            $this->appendToFile($filePath, $input->toJson());

            return file_exists($filePath);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Generates a filename based on the model's properties and source name.
     *
     * @param Model $input The model instance.
     * @param string $sourceName The base name for the file.
     *
     * @return string The generated filename.
     */
    private function generateFileName(Model $input, string $sourceName): string
    {
        $fileName = $sourceName;
        if (property_exists($input, 'id')) {
            $fileName .= '_' . $input->id;
        }
        return $fileName . '.txt';
    }

    /**
     * Appends content to the specified file.
     *
     * @param string $filePath The path of the file where content is to be appended.
     * @param string $content The content to be appended.
     */
    private function appendToFile(string $filePath, string $content): void
    {
        file_put_contents($filePath, $content, FILE_APPEND);
    }
}