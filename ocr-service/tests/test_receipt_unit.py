from app.receipt_parser import _extract_unit


def test_extract_common_purchase_units() -> None:
    assert _extract_unit("1 PACK X 37.500") == "PAK"
    assert _extract_unit("2 RIM @ 65.000") == "RIM"
    assert _extract_unit("10 PCS x 5.000") == "PCS"
    assert _extract_unit("1 JRG X 74.142,80") == "JERIGEN"
    assert _extract_unit("3 DUS x 90.000") == "DUS"


def test_product_size_is_not_treated_as_purchase_unit() -> None:
    assert _extract_unit("SUNLIGHT LIME PRO JERRYCAN 5 L") is None
    assert _extract_unit("KERTAS A4 100 GSM") is None
