<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress\Admin;

use ProjectsM\MembershipWordpress\Api\Data\ApiData;
use ProjectsM\MembershipWordpress\Api\Data\Option;
use ProjectsM\MembershipWordpress\Api\Data\OptionsCategory;
use ProjectsM\MembershipWordpress\Api\Data\Runtime;
use ProjectsM\MembershipWordpress\Api\FileStorage;
use ProjectsM\MembershipWordpress\Api\MembershipCloud;
use ProjectsM\MembershipWordPress\WordPress\Plugin;


/**
 * This classes uses the internal \WP_List_Table.
 *
 * Although this class is marked as internal, it is still used. If there is a breaking change
 * in WordPress regarding this class, the last working version can be just copied into this class
 * to not rely on WordPress anymore.
 */
class SignUpsTable extends \WP_List_Table
{
    /**
     * @var Plugin
     */
    private $plugin;


    /**
     * @var MembershipCloud
     */
    private $cloud;


    /**
     * @var array<int,ApiData|null>
     */
    private $data = [];


    /**
     * @var int[]
     */
    private $contractIds = [];


    /**
     * @var array<int,string>
     */
    private $contractNames = [];


    /**
     * @var FileStorage
     */
    private $fileStorage;


    /**
     * @inheritDoc
     */
    public function __construct (Plugin $plugin, MembershipCloud $cloud, FileStorage $fileStorage)
    {
        parent::__construct([]);
        $this->plugin = $plugin;
        $this->cloud = $cloud;
        $this->fileStorage = $fileStorage;

        foreach ($this->cloud->fetchAllContracts() as $contract)
        {
            $this->contractNames[$contract["id"]] = $contract["name"];
        }
    }


    /**
     * @inheritdoc
     */
    public function prepare_items ()
    {
        global $wpdb;

        $this->items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pm_membership_sign_ups ORDER BY time_created DESC", \ARRAY_A);

        foreach ($this->items as $item)
        {
            $this->contractIds[(int) $item["contract_id"]] = true;
        }

        // the headers use the contract ids
        $this->_column_headers = [$this->get_columns(), [], []];

        $this->set_pagination_args([
            "total_items" => count($this->items),
            "per_page" => count($this->items),
            "total_pages" => 1,
        ]);
    }

    public function get_columns ()
    {
        $columns = [
            "name" => "Name",
            "contract" => "Vertrag",
            "city" => "Stadt",
            "phone" => "Telefon",
            "email" => "E-Mail",
            "runtime" => "Laufzeit",
            "monthly_fee" => "Beitrag mtl.",
        ];
		$hasMagiclineApi = false;

        foreach (\array_keys($this->contractIds) as $contractId)
        {
            $contract = $this->getApiData($contractId);

            if (null !== $contract)
            {
				if (!$hasMagiclineApi)
				{
					$hasMagiclineApi = $contract->client->hasMagiclineApi;
				}

                foreach ($contract->optionsCategories as $optionsCategory)
                {
                    if ($optionsCategory->showInOverview)
                    {
                        $columns["options_category_{$optionsCategory->id}"] = \esc_html($optionsCategory->headline);
                    }
                }
            }
        }

        $columns["confirmed"] = "Bestätigt?";

		if ($hasMagiclineApi)
		{
			$columns["magicline"] = "Magicline";
		}

        $columns["created"] = "Erstellt";

        return $columns;
    }

    /**
     * @inheritdoc
     */
    public function column_default ($item, $column_name)
    {
		$timeConfirmed = $item["time_confirmed"];

        if ("created" === $column_name)
        {
            return \DateTime::createFromFormat("Y-m-d H:i:s", $item["time_created"])->format("d.m.Y, H:i:s") . " Uhr";
        }

        if ("confirmed" === $column_name)
        {
            if (null === $timeConfirmed)
            {
                return "nein";
            }

            $storagePath = $this->fileStorage->getPdfStoragePath($item["confirmation_code"]) . "/{$item["confirmation_code"]}.pdf";
            $storageUrl = $this->fileStorage->getPdfStorageUrl($item["confirmation_code"]) . "/{$item["confirmation_code"]}.pdf";

            return \is_file($storagePath)
                ? sprintf('<a href="%s" target="_blank">ja</a>', $storageUrl)
                : "ja";
        }

        if ("contract" === $column_name)
        {
            return $this->contractNames[$item["contract_id"]] ?? "n/a";
        }

        $data = \json_decode($item["data"], true);
        $apiData = $this->getApiData((int) $item["contract_id"]);

        if ("name" === $column_name)
        {
            return \esc_html("{$data["form"]["firstName"]} {$data["form"]["lastName"]}") . $this->row_actions([
                "remove" => sprintf(
                    '<a href="%s">Löschen</a>',
                    $this->plugin->generateAdminUrl("sign-ups", ["remove" => $item["id"]])
                ),
            ]);
        }

        if ("monthly_fee" === $column_name)
        {
            return "ca. " . \number_format((int) $data["monthlyFee"] / 100, 2, ",", ".") . "€";
        }

        if ("phone" === $column_name)
        {
            $content = "Mobil: " . \esc_html($data["form"]["mobile"]);

            return !empty($data["form"]["phone"])
                ? "Telefon: " . \esc_html($data["form"]["phone"]) . '<br>' . $content
                : $content;
        }

		if ("magicline" === $column_name)
		{
			$magicLineData = null !== $item["magicline_data"]
				? \json_decode($item["magicline_data"], true)
				: null;
			$exported = $magicLineData["exported"] ?? false;
			$message = $magicLineData["message"] ?? null;

			$tryAgainLink = sprintf(
				'<a href="%s">Jetzt übertragen</a>',
				\admin_url("admin-ajax.php?action=pm_magicline_transfer&signup={$item["id"]}")
			);

			// remove the try again link if not yet exported
			if (null === $timeConfirmed)
			{
				$tryAgainLink = "";
			}

			if (null === $magicLineData)
			{
				return "<em>Nicht übertragen</em><br>{$tryAgainLink}";
			}

			if (!$exported)
			{
				return "<em>Übertragen, aber nicht exportiert.</em><br>"
					. ($message ? \esc_html($message) . "<br>": "")
					. $tryAgainLink;
			}

			$output = "";

			if ($message)
			{
				$output .= $message . "<br>";
			}

			$id = \esc_html($magicLineData["data"]["id"]);
			$output .= "<strong>Übertragen mit ID <code>{$id}</code></strong>";

			return $output;
		}

        if (0 !== \preg_match('~^options_category_(?<category_id>\d+)$~', $column_name, $matches))
        {
            if (null === $apiData || !isset($data["options"][$matches["category_id"]]))
            {
                return "—";
            }

            $category = $this->findOptionsCategoryById($apiData->optionsCategories, (int) $matches["category_id"]);

            if (null === $category)
            {
                return "—";
            }

            $options = [];

            foreach ($data["options"][$matches["category_id"]] as $optionId => $value)
            {
                if ("true" === $value)
                {
                    $option = $this->findOptionById($category->options, (int) $optionId);

                    if (null !== $option)
                    {
                        $options[] = \esc_html($option->name);
                    }
                }
            }

            return !empty($options)
                ? implode(", ", $options)
                : "—";
        }

        if ("runtime" === $column_name)
        {
            $runtime = $apiData !== null
                ? $this->findRuntimeById($apiData->runtimes, (int) $data["runtime"])
                : null;

            return null !== $runtime
                ? $runtime->title
                : "(ID: {$data["runtime"]})";
        }

        return isset($data["form"][$column_name])
            ? \esc_html($data["form"][$column_name])
            : "—";
    }


    /**
     * Returns the api data for the given contract
     *
     * @param int $contract
     * @return null|ApiData
     */
    private function getApiData (int $contract)
    {
        if (!\array_key_exists($contract, $this->data))
        {
            $this->data[$contract] = $this->cloud->fetchApiData($contract, false);
        }

        return $this->data[$contract];
    }


    /**
     * @param Runtime[] $runtimes
     * @param int   $runtimeId
     * @return null|Runtime
     */
    private function findRuntimeById (array $runtimes, int $runtimeId)
    {
        foreach ($runtimes as $runtime)
        {
            if ($runtime->id === $runtimeId)
            {
                return $runtime;
            }
        }

        return null;
    }


    /**
     * @param OptionsCategory[] $categories
     * @param int   $id
     * @return null|OptionsCategory
     */
    private function findOptionsCategoryById (array $categories, int $id)
    {
        foreach ($categories as $category)
        {
            if ($category->id === $id)
            {
                return $category;
            }
        }

        return null;
    }


    /**
     * @param Option[] $options
     * @param int   $id
     * @return null|Option
     */
    private function findOptionById (array $options, int $id)
    {
        foreach ($options as $option)
        {
            if ($option->id === $id)
            {
                return $option;
            }
        }

        return null;
    }
}
