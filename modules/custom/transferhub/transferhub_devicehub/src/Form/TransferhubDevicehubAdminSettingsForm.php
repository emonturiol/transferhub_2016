<?php
/**
 * @file
 * Contains \Drupal\google_analytics\Form\GoogleAnalyticsAdminSettingsForm.
 */

namespace Drupal\transferhub_devicehub\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Google_Analytics settings for this site.
 */
class TransferhubDevicehubAdminSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'transferhub_devicehub_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['transferhub_devicehub.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('transferhub_devicehub.settings');
        
        //kint($config);

        $form['devicehub_server_host'] = [
            //'#default_value' => $config->get('account'),
            '#description' => $this->t(''),
            '#maxlength' => 100,
            '#required' => TRUE,
            '#size' => 50,
            '#title' => $this->t('DeviceHub Server Host'),
            '#type' => 'textfield',
        ];

        $form['devicehub_username'] = [
            //'#default_value' => $config->get('account'),
            '#description' => $this->t(''),
            '#maxlength' => 100,
            '#required' => TRUE,
            '#size' => 20,
            '#title' => $this->t('User name'),
            '#type' => 'textfield',
        ];

        $form['devicehub_password'] = [
            //'#default_value' => $config->get('account'),
            '#description' => $this->t(''),
            '#maxlength' => 100,
            '#required' => TRUE,
            '#size' => 20,
            '#title' => $this->t('Password'),
            '#type' => 'textfield',
        ];

        $form['devicehub_default_db'] = [
            '#default_value' => $config->get('default_db'),
            '#description' => $this->t(''),
            '#maxlength' => 100,
            '#required' => TRUE,
            '#size' => 15,
            '#title' => $this->t('Default data base'),
            '#type' => 'textfield',
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('transferhub_devicehub.settings');
        $config
            ->set('server_host', $form_state->getValue('devicehub_server_host'))
            ->set('username', $form_state->getValue('devicehub_username'))
            ->set('password', $form_state->getValue('devicehub_password'))
            ->set('default_db', $form_state->getValue('devicehub_default_db'))
            ->save();

        /*if ($form_state->hasValue('google_analytics_translation_set')) {
            $config->set('translation_set', $form_state->getValue('google_analytics_translation_set'))->save();
        }*/

        parent::submitForm($form, $form_state);
    }

}