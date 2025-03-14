import json

with open("colours.json") as input_file:
    data = json.load(input_file)

    colours = next(
        x["data"] for x in data if x["type"] == "table" and x["name"] == "colours"
    )

    converted_colours = [
        list(int(colour["rgb"][i : i + 2], 16) for i in (0, 2, 4)) for colour in colours
    ]

    colours_to_info = {
        colour["rgb"].lower(): {"id": colour["id"], "name": colour["name"]}
        for colour in colours
    }

    with open("converted_colours.json", "w", encoding="utf-8") as output_file1:
        json.dump(converted_colours, output_file1, ensure_ascii=False, indent=2)

    with open("colours_to_info.json", "w", encoding="utf-8") as output_file2:
        json.dump(colours_to_info, output_file2, ensure_ascii=False, indent=2)
