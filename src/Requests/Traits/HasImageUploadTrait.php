<?php namespace BinshopsBlog\Requests\Traits;



trait HasImageUploadTrait
{
    /**
     * @param $size
     * @return \Illuminate\Http\UploadedFile|null
     */
    public function get_image_file($size)
    {

        if ($this->file($size)) {
            return $this->file($size);
        }

        return null;
    }

}