<?PHP
namespace LiveTranslator;

use \Nette;
use \Latte;
use \Tracy\IBarPanel;
use \Tracy\Debugger;


/*
 * @todo hence why the window.open to show the error does not appear? // google translate
 * @todo disable saving / or somehow invent it when editing and deleting all text //google translate
 * @todo when looking for a string not then click on the translation the first time // google translate
 */
class Panel implements \Tracy\IBarPanel
{
	use \Nette\SmartObject;
	
	const XHR_HEADER = 'X-Translation-Client';

	const LANGUAGE_KEY = 'X-LiveTranslator-Lang',
	      NAMESPACE_KEY = 'X-LiveTranslator-Ns';

	/** @var string */
	public $layout = 'horizontal';

	/** @var int */
	public  $height = 125;

	/** @var Translator */
	protected $translator;

	/** @var Nette\Http\IRequest */
	protected $httpRequest;

	/** @var Latte\Engine */
	private $latte;
	
	/** @var string|NULL */
	private $tempDir;
	
        /**
	 * @param Translator $translator
	 * @param Nette\Http\IRequest $httpRequest
	 * @throws Nette\InvalidArgumentException
	 */
	public function __construct(?string $tempDir,Translator $translator, Nette\Http\IRequest $httpRequest)
	{
		$this->translator = $translator;
		$this->httpRequest = $httpRequest;
		$this->tempDir = $tempDir;

		$this->processRequest();
	}



	public function getLayout()
	{
		return $this->layout;
	}



	public function getHeight()
	{
		return $this->height;
	}



	public function getTranslator()
	{
		return $this->translator;
	}



	public function setLayout($layout)
	{
		if (!in_array($layout, array('horizontal', 'vertical'))){
			throw new Nette\InvalidArgumentException("Unknown layout $layout.");
		}
		$this->layout = $layout;
		return $this;
	}



	public function setHeight($height)
	{
		if (!is_numeric($height)){
			throw new Nette\InvalidArgumentException("Height must be integer.");
		}
		$this->height = $height;
		return $this;
	}



	/**
	 * Returns the code for the panel tab.
	 * @return string
	 */
	public function getTab()
	{
		$latte = new Latte\Engine;
		return $latte->renderToString(__DIR__ . '/tab.phtml');
	}



	/**
	 * Returns the code for the panel.
	 * @return string
	 */
	public function getPanel()
	{
		

		$latte = $this->createTemplate();
		$file = $this->translator->isCurrentLangDefault() ? '/panel.inactive.phtml' : '/panel.phtml';
		$parameters = array();
		$parameters['panel'] = $this;
		$parameters['translator'] = $this->translator;
		$parameters['lang'] = $this->translator->getCurrentLang();
		if ($this->translator->getPresenterLanguageParam()){
			$parameters['availableLangs'] = $this->translator->getAvailableLanguages();
			
		}
		else {
			$parameters['availableLangs'] = NULL;

		}
		return $latte->renderToString(__DIR__ . $file, $parameters);

	}



	public function getLink($toLang)
	{
		return $this->translator->getPresenterLink($toLang);
	}



	/**
	 * Handles incoming request and sets translations.
	 */
	private function processRequest()
	{
		if ($this->httpRequest->isMethod('post') && $this->httpRequest->isAjax() && $this->httpRequest->getHeader(self::XHR_HEADER)) {
			$data = json_decode(file_get_contents('php://input'));

			if ($data) {
				$this->translator->setCurrentLang($data->{self::LANGUAGE_KEY});
				if ($data->{self::NAMESPACE_KEY}) $this->translator->setNamespace($data->{self::NAMESPACE_KEY});

				unset($data->{self::LANGUAGE_KEY}, $data->{self::NAMESPACE_KEY});

				foreach ($data as $string => $translated){
					$this->translator->setTranslation($string, $translated);
				}
			}
			exit;
		}
	}


	private function createTemplate()
	{
		$latte = new Latte\Engine;
		$latte->addFilter('ordinal', function($n){
			switch (substr($n, -1)) {
				case 1:
					return 'st';
				case 2:
					return 'nd';
				case 3:
					return 'rd';
				default:
					return 'th';
			}
		});

		return $latte;
	}
}
