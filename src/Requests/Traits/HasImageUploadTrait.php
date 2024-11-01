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

        // not found? lets cycle through all the images. if a larger image was submitted, use that instead
        foreach (config("binshopsblog.image_sizes") as $image_size_name => $image_size_info) {
            if ($image_size_name == $size) {
                break;
            }
            if ($this->file($image_size_name)) {
                return $this->file($image_size_name);
            }
        }

        return null;


    }

}