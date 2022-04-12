<?php declare(strict_types=1);
namespace Imbo\Model;

class ArrayModel implements ModelInterface
{
    /**
     * Data
     *
     * @var array<string,mixed>
     */
    private array $data = [];

    /**
     * Title of the model, used in representations
     */
    private ?string $title = null;

    /**
     * Set the data
     *
     * @param array<string,mixed> $data The data to set
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the title of the model
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the title of the model
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }
}
